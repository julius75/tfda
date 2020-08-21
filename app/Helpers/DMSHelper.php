<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 11/15/2017
 * Time: 4:18 PM
 */

namespace app\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use GuzzleHttp\Client as Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use \CurlFile;
use App\User;
use Illuminate\Http\Request;

class DMSHelper
{
    //hiram code on the dms functions
    static function authDms($empty)
    {

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        $data = '';
        try {
            $usr_name = env('DMS_ADMINUSR', 'admin');
            $usr_password = env('DMS_ADMINPASSWORD', 'admin');

            $url = env('DMS_URL');

            $res = $client->request('GET', 'service/api/login?u=' . $usr_name . '&pw=' . $usr_password . '&format=json', ['exceptions' => false]);

            $success = false;

            if ($res->getStatusCode() == 200) {

                $res->getBody()->rewind();

                $response = json_decode((string)$res->getBody());

                $data = array('success' => true, 'message' => $response->data, 'ticket' => $response->data->ticket);

            } else if ($res->getStatusCode() == 403) {
                $res->getBody()->rewind();

                $response = json_decode((string)$res->getBody());

                $data = array('success' => $success, 'message' => $response->message);

            } else {
                $response = json_decode((string)$res->getBody());

                $data = array('success' => false, 'user_id' => $response->message);

            }

        } catch (RequestException $e) {

            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            } else {
                $data = array('success' => false, 'message' => 'Check DMS Connection.');

            }
        }

        return $data;
    }

    static function validateTicketDMS($ticket)
    {
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml',
            ]
        ]);
        $url = env('DMS_URL');

        try {


            $response = $client->request('GET', 'service/api/login/ticket/' . $ticket, ['exceptions' => false])->getBody()->getContents();

            $responseXml = simplexml_load_string($response);
            if ($responseXml instanceof \SimpleXMLElement) {

                $response_ticket = (string)$responseXml[0];

            }
            if ($response_ticket == $ticket) {
                $func_response = array('success' => true, 'message' => 'Tokey validated successfully');
            } else {
                $func_response = array('success' => false, 'message' => 'In Valid Tokey Loogin to continue');
            }


        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $func_response = array('success' => false, 'message' => $response);

            }
        }
        return $func_response;
    }

    static function logoutDMS($ticket)
    {

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml',
            ]
        ]);

        try {
            $response = $client->request('DELETE', 'service/api/login/ticket/' . $ticket, ['exceptions' => false])->getBody()->getContents();

            $responseXml = simplexml_load_string($response);
            if ($responseXml instanceof \SimpleXMLElement) {
                $response_code = (string)$responseXml->status->code[0];

                $response_message = (string)$responseXml->message;

                if ($response_code == 200) {
                    $func_response = array('success' => true, 'message' => 'The ticket has been log-out successfully');
                } else {
                    $func_response = array('success' => false, 'message' => 'Ticket not found or has not been removed.');
                }

            }


        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $func_response = array('success' => false, 'message' => $response);

            }
        }

        return $func_response;
    }
    ////update the user details


    //then create users 
    public static function dmsCreateAccount($user_data)
    {
        //authenticate and then create account 
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {

            $response = $client->request('POST', 'service/api/people?alf_ticket=' . $ticket,
                ['json' => $user_data]
            )->getBody()->getContents();

            $response = json_decode((string)$response);

            if ($user_data['userName'] == $response->userName) {

                $data = array('success' => true, 'message' => 'User Account created successfully', 'user_data' => $response);

            } else {

                $data = array('success' => false, 'user_id' => $response->data);

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

//delete user
    public static function dmsDeleteAccount($userName)
    {


        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            $response = $client->request('DELETE', 'service/api/people/' . $userName, ['exceptions' => true])
                ->getBody()
                ->getContents();

            if ($response) {

                $data = array('success' => true, 'message' => 'User Account deleted successfully');

            } else {
                $data = array('success' => true, 'message' => 'User Account deleted failed, try again or contact system admin');

            }

        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

//update user details
    public static function dmsUpdateAccount($userName, $userDetails)
    {
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {

            $response = $client->request('PUT', 'service/api/people/' . $userName,
                ['json' => $userDetails]
            )->getBody()->getContents();

            $response = json_decode((string)$response);

            if (isset($response->userName)) {

                $data = array('success' => true, 'message' => 'Account details updated successfullly');

            } else {

                if ($response->status->code == 404) {
                    $data = array('success' => false, 'message' => $response->message);

                } else {
                    $data = array('success' => false, 'message' => $response->message);

                }
            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

//change pasword 

    public static function dmsUpdateAccountPassword($userName, $oldPassword, $newPassword)
    {
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        $userPasword = array('newpw' => $newPassword, 'oldpw' => $oldPassword);

        try {

            $response = $client->request('POST', 'service/api/person/changepassword/' . $userName,
                ['json' => $userPasword]
            )->getBody()->getContents();

            $response = json_decode((string)$response);

            if (isset($response->success) && $response->success == 1) {

                $data = array('success' => true, 'message' => 'Account password updated successfullly');

            } else {
                $data = array('success' => false, 'message' => 'Account password not updated, try again!');

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

    public static function dmsGetAccount($userName)
    {
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            $response = $client->request('GET', 'service/api/people/' . $userName, ['exceptions' => false])->getBody()->getContents();

            $response = json_decode((string)$response);

            if (isset($response->userName)) {

                $data = array('success' => true, 'message' => 'Account details found', 'user_data' => $response);

            } else {

                if ($response->status->code == 404) {
                    $data = array('success' => false, 'message' => $response->message);

                } else {
                    $data = array('success' => false, 'message' => $response->message);

                }
            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

    public static function dmsGetAllAccount()
    {
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            $response = $client->request('GET', 'service/api/people', ['exceptions' => false])->getBody()->getContents();

            $response = json_decode((string)$response);

            if (isset($response->people)) {

                $data = array('success' => true, 'message' => 'DMS User Accounts Details found', 'user_data' => $response->people);

            } else {
                $data = array('success' => false, 'message' => 'DMS User Accounts Details not found, check your connection');

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }
//to sites 
//get the root site 

//get the root site 
    public static function dmsCreateAppSiteRoot($site_details)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request('POST', 'service/api/sites?alf_ticket=' . $ticket, [
                'json' => $site_details
            ], ['exceptions' => false])
                ->getBody()
                ->getContents();

            $response = json_decode((string)$response);

            if (isset($response)) {
                $nodeRef = str_replace("/alfresco/service/api/node/workspace/SpacesStore/", "", $response->node);

                $data = array('success' => true, 'message' => 'DMS Site Details Created Successfully', 'node_ref' => $nodeRef, 'root_site' => $response);

            } else {

                $data = array('success' => false, 'message' => 'DMS Site created.');

            }
        } catch (RequestException $e) {

            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody(true);
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $response = json_encode($responseBodyAsString);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

    public static function dmsGetAppSiteRoot($root_site)
    {

        //$root_site = env('DMS_APPROOTSITE', 'tfda');

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            if ($root_site == '') {
                $response = $client->request('GET', 'service/api/sites', ['exceptions' => true])->getBody()->getContents();
                $response = json_decode((string)$response);

                if (!empty($response)) {

                    $data = array('success' => true, 'message' => 'DMS Root Site ', 'root_sites' => $response);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Not configured');

                }
            } else {
                $response = $client->request('GET', 'service/api/sites/' . $root_site, ['exceptions' => true])->getBody()->getContents();
                $response = json_decode((string)$response);

                if (isset($response->shortName)) {
                    $nodeRef = str_replace("/alfresco/service/api/node/workspace/SpacesStore/", "", $response->node);

                    $data = array('success' => true, 'message' => 'DMS Root Site ', 'node_ref' => $nodeRef, 'root_site' => $response);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Not configured');

                }
            }


        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

    public static function dmsGetAppSiteContainer($site_id, $container)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {

            if ($container != '') {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/sites/' . $site_id . '/containers/' . $container . '?alf_ticket=' . $ticket, ['exceptions' => true])->getBody()->getContents();
                $response = json_decode((string)$response);

                if (isset($response->entry)) {
                    $container_entries = $response->entry;
                    $data = array('success' => true, 'message' => 'DMS Root Site Container', 'site_container' => $container_entries);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Container Not configured');

                }
            } else {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/sites/' . $site_id . '/containers', ['exceptions' => true])->getBody()->getContents();

                if (isset($response->list)) {
                    $container_entries = $response->list->entries[0]->entry;
                    $data = array('success' => true, 'message' => 'DMS Root Site Container', 'site_container' => $container_entries);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Container Not configured');

                }
            }

        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

    public static function dmsGetAppSiteContainerNodes($site_id, $container)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {

            $response = $client->request('GET', 'service/slingshot/doclib/doclist/node/site/' . $site_id . '/' . $container . '?alf_ticket=' . $ticket, ['exceptions' => true])->getBody()->getContents();
            $response = json_decode((string)$response);

            if (isset($response->items)) {
                $container_nodes = $response->items;
                $data = array('success' => true, 'message' => 'DMS Root Site Container Nodes', 'container_nodes' => $container_nodes);

            } else {
                $data = array('success' => false, 'message' => 'DMS Root Site Container Nodes Not configured');

            }


        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

    public static function dmsGetAppRootNodes($defination)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            if ($defination == '-root-') {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/-root-?alf_ticket=' . $ticket, ['exceptions' => true])->getBody()->getContents();
                $response = json_decode((string)$response);

                if (isset($response->entry)) {
                    $approot_node = $response->entry;
                    $data = array('success' => true, 'message' => 'DMS Root Site Container Nodes', 'approot_node' => $approot_node);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Container Nodes Not configured');

                }
            } else {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/' . $defination . '/?alf_ticket=' . $ticket, ['exceptions' => true])->getBody()->getContents();
                $response = json_decode((string)$response);

                if (isset($response->entry)) {
                    $approot_node = $response->entry;
                    $data = array('success' => true, 'message' => 'DMS Root Site Container Nodes', 'approot_node' => $approot_node);

                } else {
                    $data = array('success' => false, 'message' => 'DMS Root Site Container Nodes Not configured');

                }

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;

    }

    public static function dmsGetAppRootNodesChildren($parent_node)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/' . $parent_node . '/children/?alf_ticket=' . $ticket, ['exceptions' => true])->getBody()->getContents();
            $response = json_decode((string)$response);

            if (isset($response->list)) {

                $node_children = $response->list->entries;
                $data = array('success' => true, 'message' => 'DMS Node Children', 'node_children' => $node_children);

            } else {

                $data = array('success' => false, 'message' => 'DMS Node Children Not configured');

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

//get node contents
    public static function dmsGetAppRootNodesContents($parent_node)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/' . $parent_node . '/content?alf_ticket=' . $ticket,
                ['exceptions' => true]
            )
                ->getBody()
                ->getContents();
            $response = json_decode((string)$response);

            if (isset($response->list)) {

                $node_children = $response->list->entries;
                $data = array('success' => true, 'message' => 'DMS Node Children', 'node_children' => $node_children);

            } else {

                $data = array('success' => false, 'message' => 'DMS Node Children Not configured');

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

//get node versions
    public static function convertSingleDimensionEntryDataset($node_versions, $node_ref)
    {
        $data = array();
        foreach ($node_versions as $obj) {
            $entry = $obj->entry;

            $data[] = array('modifiedAt' => $entry->modifiedAt,
                'name' => $entry->name,
                //'versionComment'=>$entry->versionComment,
                'id' => $entry->id,
                'nodeType' => $entry->nodeType,
                'node_ref' => $node_ref
            );

        }
        return $data;

    }

    public static function dmsGetNodePreviousVersions($node_ref, $version_ref)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            if ($version_ref != '') {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/' . $node_ref . '/versions/' . $version_ref . '?alf_ticket=' . $ticket,
                    ['exceptions' => true]
                )
                    ->getBody()
                    ->getContents();
                $response = json_decode((string)$response);

                if (isset($response->entry)) {

                    $node_versions = $response->entry;
                    $data = array('success' => true, 'message' => 'DMS Node Previous Versions', 'node_versions' => $node_versions);

                } else {

                    $data = array('success' => false, 'message' => 'DMS Node Children Not configured');

                }
            } else {
                $response = $client->request('GET', 'api/-default-/public/alfresco/versions/1/nodes/' . $node_ref . '/versions?alf_ticket=' . $ticket,
                    ['exceptions' => true]
                )
                    ->getBody()
                    ->getContents();
                $response = json_decode((string)$response);

                if (isset($response->list)) {

                    $node_versions = $response->list->entries;
                    $node_versions = self::convertSingleDimensionEntryDataset($node_versions, $node_ref);

                    $data = array('success' => true, 'message' => 'DMS Node Previous Versions', 'node_versions' => $node_versions);

                } else {

                    $data = array('success' => false, 'message' => 'DMS Node Children Not configured');

                }

            }

        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

//create a new node
    public static function dmsCreateAppRootNodesChildren($parent_node, $node_details)
    {
        $auth_resp = self::authDms('');
        $ticket = $auth_resp['ticket'];
        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
        try {
            $response = $client->request('POST', 'api/-default-/public/alfresco/versions/1/nodes/' . $parent_node . '/children?alf_ticket=' . $ticket,
                [
                    'json' => $node_details
                ])
                ->getBody()
                ->getContents();
            $response = json_decode((string)$response);
            if (isset($response->entry)) {

                $node_details = $response->entry;

                $data = array('success' => true, 'message' => 'DMS Node Children', 'node_details' => $node_details);

            } else {

                $data = array(
                    'success' => false,
                    'message' => 'DMS Node Children Not configured'
                );
            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);
            }
        }
        return $data;
    }

    public static function dmsUpdateAppRootNodesChildren($node_id, $node_details)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request('PUT', 'api/-default-/public/alfresco/versions/1/nodes/' . $node_id . '?alf_ticket=' . $ticket,
                [
                    'json' => $node_details
                ])
                ->getBody()
                ->getContents();

            $response = json_decode((string)$response);


            if (isset($response->entry)) {

                $node_details = $response->entry;

                $data = array('success' => true, 'message' => 'DMS Node Children', 'node_details' => $node_details);

            } else {

                $data = array('success' => false, 'message' => 'DMS Node Details Not configured');

            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

//delete node
    public static function dmsDeleteAppRootNodesChildren($node_id)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request('DELETE', 'api/-default-/public/alfresco/versions/1/nodes/' . $node_id . '?alf_ticket=' . $ticket)
                ->getBody()
                ->getContents();

            $response = json_decode((string)$response);
            $data = array('success' => true, 'message' => 'DMS NODE Deleted successfully');

        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);

                $data = array('success' => false, 'message' => $response);

            }
        }
        return $data;
    }

//get root folder container
    public static function dmsUploadNodeDocument($destination_node, $document_path, $origFileName, $update_noderef, $description)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        $client = new Client([
            'base_uri' => env('DMS_URL'),
            'auth' => [env('DMS_ADMINUSR', 'admin'), env('DMS_ADMINPASSWORD', 'admin')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $destination_node = 'workspace://SpacesStore/' . $destination_node;
            if ($update_noderef != '') {

                $update_noderef = 'workspace://SpacesStore/' . $update_noderef;

            }
            $upload_url = env('DMS_URL') . 'service/api/upload?alf_ticket=' . $ticket;
            $curl_request = curl_init();
            curl_setopt($curl_request, CURLOPT_URL, $upload_url);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_request, CURLOPT_HEADER, false);
            curl_setopt($curl_request, CURLOPT_POST, true);

            $args = new CurlFile($document_path, 'text/plain', $origFileName);

            curl_setopt($curl_request, CURLOPT_POSTFIELDS, array('contenttype' => 'cm:content', 'description' => 'Document Upload', 'filename' => $origFileName, 'updateNodeRef' => $update_noderef, 'destination' => $destination_node, 'filedata' => $args));

            $result = curl_exec($curl_request);
            $response = json_decode((string)$result);

            if (isset($response->nodeRef)) {
                $nodeRef = str_replace("workspace://SpacesStore/", "", $response->nodeRef);
                $workspace = $response->nodeRef;
                unlink($document_path);
                $data = array(
                    'success' => true,
                    'message' => 'Document Uploaded Successfully',
                    'document_upload' => $response,
                    'nodeRef' => $nodeRef,
                    'workspace' => $workspace
                );
            } else {
                $data = array('success' => false, 'message' => 'DMS Node Children Not configured');
            }
        } catch (RequestException $e) {
            //echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $response_msg = $e->getMessage();
                $response = json_encode($response_msg);
                $data = array(
                    'success' => false,
                    'message' => $response
                );
            }
        }
        return $data;
    }

//get node versions
    static function downloadDocumentUrl($node_ref, $version_id)
    {
        $auth_resp = authDms('');
        $ticket = $auth_resp['ticket'];

        if ($version_id != '') {
            $url = env('DMS_URL') . 'service/api/node/content/workspace/SpacesStore/' . $node_ref . '/version/' . $version_id . '?a=false&alf_ticket=' . $ticket;

        } else {
            $url = env('DMS_URL') . 'service/api/node/content/workspace/SpacesStore/' . $node_ref . '?a=false&alf_ticket=' . $ticket;

        }
        return $url;
    }

    static function getApplicationSubModuleNodeDetails($section_id, $module_id, $sub_module_id, $user_id, $con = 'mysql')
    {
        $record = '';
        $root_site = env('DMS_APPROOTSITE', 'tfda');
        $root_site_id = env('DMS_APPROOTSITE_ID', '2');
      
        $rec = DB::connection($con)->table('tra_sectionssubmodule_docdefination as t1')
            ->select('t1.node_ref')
            ->join('tra_sections_docdefination as t2', 't1.doc_section_id', '=', 't2.id')
            ->join('tra_dmsdocument_sites as t3', 't2.dms_site_id', '=', 't3.id')
            ->where(array('t2.section_id' => $section_id, 't3.site_id' => $root_site_id, 't1.sub_module_id' => $sub_module_id))
            ->first();
            
        if ($rec) {
            $record = $rec;
          
        } else {
            //createa node for the module and submodule 
            //root site node ref
            $table_name = 'tra_sections_docdefination';
            $where_section = array('section_id' => $section_id, 'dms_site_id' => $root_site_id);
            $record = getPreviousRecords($table_name, $where_section);

            if (count($record['results']) > 0) {

                $section_node_ref = $record['results']['0']['node_ref'];
                $doc_section_id = $record['results']['0']['id'];
            } else {
                //create a node and save informaiton
                $site_node_ref = getSingleRecordColValue('tra_dmsdocument_sites', array('id' => 2), 'node_ref');
                $table_data = array('section_id' => $section_id, 'dms_site_id' => $root_site_id);
                $resp = self::funcCreatApplicationNode($table_name, $where_section, $section_id, 'par_sections', $site_node_ref, $table_data, $user_id);

                $section_node_ref = $resp['node_ref'];
                $doc_section_id = $resp['record_id'];

            }
            //module_id
            $table_name = 'tra_sectionsmodule_docdefination';
            $where_module = array('module_id' => $module_id, 'doc_section_id' => $doc_section_id);
            $record = getPreviousRecords($table_name, $where_module);
            if (count($record['results']) > 0) {

                $module_node_ref = $record['results']['0']['node_ref'];
                $doc_module_id = $record['results']['0']['id'];
            } else {
                //create a node and save informaiton
                $table_data = array('doc_section_id' => $doc_section_id, 'module_id' => $module_id);
                $resp = self::funcCreatApplicationNode($table_name, $where_module, $module_id, 'modules', $section_node_ref, $table_data, $user_id);
                $module_node_ref = $resp['node_ref'];
                $doc_module_id = $resp['record_id'];

            }

            $table_name = 'tra_sectionssubmodule_docdefination';
            $where_submodule = array('sub_module_id' => $sub_module_id, 'doc_module_id' => $doc_module_id);
            $record = getPreviousRecords($table_name, $where_submodule);
            if (count($record['results']) > 0) {
                $record = $record['results']['0'];
            } else {
                //create a node and save informaiton
                $table_data = array('doc_section_id' => $doc_section_id,
                    'doc_module_id' => $doc_module_id,
                    'module_id' => $module_id,
                    'sub_module_id' => $sub_module_id);
                $resp = self::funcCreatApplicationNode($table_name, $where_submodule, $sub_module_id, 'sub_modules', $module_node_ref, $table_data, $user_id);
                $sub_module_node_ref = $resp['node_ref'];

                $record = array('node_ref' => $sub_module_node_ref);
            }
        }
        
        return (object)$record;
    }

    static function funcCreatApplicationNode($table_name, $where_state, $variable_id, $table_variable, $site_node_ref, $table_data, $user_id)
    {
        $variable_name = getSingleRecordColValue($table_variable, array('id' => $variable_id), 'name');
        $node_name = strtoupper(str_replace(' ', '', $variable_name));
        $node_details = array('name' => $node_name,
            'nodeType' => 'cm:folder');

        $response = dmsCreateAppRootNodesChildren($site_node_ref, $node_details);

        $node_ref = '';
        $record_id = '';
        if ($response['success']) {
            $node_id = $response['node_details']->id;
            $table_data['node_ref'] = $node_id;

            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;

            $table_data['node_name'] = $node_name;

            $res = insertRecord($table_name, $table_data, $user_id);

            $node_ref = $node_id;
            $record_id = $res['record_id'];
        }
        return array('node_ref' => $node_ref, 'record_id' => $record_id);
    }

    static function getApplicationRootNode($application_code)
    {
        $record = '';
        $root_site = env('DMS_APPROOTSITE', 'tfda');
        $rec = DB::connection('mysql')->table('tra_application_documentsdefination as t1')
            ->select('t1.*')
            ->where(array('t1.application_code' => $application_code))
            ->first();
        if ($rec) {
            $record = $rec;
        }
        return $record;
    }
   

    
    static function getNonStructuredDestinationNode($auth_ticket, $document_type_id, $document_site_id, $trader_email = 'dms')
    {

        $record = '';
        $root_site = env('DMS_APPROOTSITE', 'tfda');

        $rec = DB::connection('mysql')->table('tra_nonstructured_docdefination as t1')
            ->select('t1.*')
            ->where(array('t1.document_type_id' => $document_type_id, 't1.dms_site_id' => $document_site_id))
            ->first();
        if ($rec) {
            $record = $rec;
        } else {

            //get the documetn Type Name
            $parentnode = getSingleRecord('tra_dmsdocument_sites', array('id' => $document_site_id), 'mysql');

            $parentnode_ref = $parentnode->node_ref;
            $document_type = getParameterItem('par_document_types', $document_type_id, 'mysql');
            $node_name = $document_type;
            $node_details = array(
                'name' => $node_name . str_random(5),
                'nodeType' => 'cm:folder');
            $response = dmsCreateAppRootNodesChildren($parentnode_ref, $node_details);
            //insert data
            $dms_node_id = $response['node_details']->id;

            $dmsapp_data = array('document_type_id' => $document_type_id,
                'dms_site_id' => $document_site_id,
                'node_ref' => $dms_node_id,
                'created_by' => $trader_email,
                'created_on' => Carbon::now());
            $response = insertRecord('tra_nonstructured_docdefination', $dmsapp_data, $trader_email, 'mysql');
            $record = (object)$dmsapp_data;

        }
        return $record;

    }

    static function getDocumentTypeRootNode($parentnode_ref, $application_code, $document_type_id, $trader_email)
    {
        $record = '';
        $root_site = env('DMS_APPROOTSITE', 'tfda');

        $rec = DB::connection('mysql')->table('tra_application_documentstypedefination as t1')
            ->select('t1.*')
            ->where(array('t1.application_code' => $application_code, 't1.document_type_id' => $document_type_id))
            ->first();
        if ($rec) {
            $record = $rec;
        } else {
            //get the documetn Type Name
            $document_type = getParameterItem('par_document_types', $document_type_id, 'mysql');
            //create nore
            $node_name = $document_type;
            $node_details = array(
                'name' => $node_name . str_random(5),
                'nodeType' => 'cm:folder');
            $response = dmsCreateAppRootNodesChildren($parentnode_ref, $node_details);
              
            if ($response['success']) {
                $dms_node_id = $response['node_details']->id;
                //save the details to the tra_application_documentstypedefination
                $dmsapp_data = array('application_code' => $application_code,
                    'document_type_id' => $document_type_id,
                    'node_ref' => $dms_node_id,
                    'created_by' => $trader_email,
                    'created_on' => Carbon::now());

                
                $response = insertRecord('tra_application_documentstypedefination', $dmsapp_data, $trader_email, 'mysql');
                $record = (object)$dmsapp_data;

            }
        }
        return $record;
    }
   
    static function getNonStructuredDocApplicationRootNode($parentnode_ref, $reference_record_id,$reference_table_name,$document_type_id,$user_id){
        $record = '';
        $root_site = env('DMS_APPROOTSITE', 'tfda');
        $where = array('id' => $reference_record_id);
        $rec = DB::connection('mysql')->table($reference_table_name.' as t1')
            ->select('t1.*')
            ->where($where)
            ->first();
      
        if ($rec) {
            $record = $rec;
            $node_ref = $record->node_ref;
           
            if($node_ref == ''){

                $document_type = getParameterItem('par_document_types', $document_type_id, 'mysql');
                //create nore
                $node_name = $document_type;
                $node_details = array(
                    'name' => $node_name . str_random(5),
                    'nodeType' => 'cm:folder');
                $response = dmsCreateAppRootNodesChildren($parentnode_ref, $node_details);
               
                if ($response['success']) {
                    $dms_node_id = $response['node_details']->id;
                    //save the details to the tra_application_documentstypedefination
                    $dmsapp_data = array(
                        'node_ref' => $dms_node_id);
                        if (recordExists($reference_table_name, $where)) {

                            $previous_data = getPreviousRecords($reference_table_name, $where);
                            $previous_data = $previous_data['results'];
                            $res = updateRecord( $reference_table_name, $previous_data, $where, $dmsapp_data, $user_id);
                            
                        }

                     $record = (object)$dmsapp_data;

                }

            }
           

        } 
        return $record;

        
    }
    static function saveApplicationDocumentNodedetails($module_id, $sub_module_id, $application_code, $tracking_no, $reference_no, $dms_node_id, $user, $con = 'mysql')
    {
        $dmsapp_data = array(
            'application_code' => $application_code,
            'tracking_no' => $tracking_no,
            'reference_no' => $reference_no,
            'dms_node_id' => $dms_node_id,
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id,
            'created_by' => $user,
            'created_on' => Carbon::now());
        $resp = insertRecord('tra_application_documentsdefination', $dmsapp_data, $user, $con);
        return $resp;
    }

    static function initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id)
    {
        $dms_node_details = self::getApplicationSubModuleNodeDetails($section_id, $module_id, $sub_module_id, $user_id);
        $nodeTracking = str_replace("/", "-", $ref_number);
        $parentNode_ref = $dms_node_details->node_ref;
        $node_details = array(
            'name' => $nodeTracking,
            'nodeType' => 'cm:folder'
        );
        $dms_res = self::dmsCreateAppRootNodesChildren($parentNode_ref, $node_details);
        if ($dms_res['success']) {
            $dms_node_id = $dms_res['node_details']->id;
            self::saveApplicationDocumentNodedetails($module_id, $sub_module_id, $application_code, '', $ref_number, $dms_node_id, $user_id);
        }
    }

}