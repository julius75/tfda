<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=10, user-scalable=yes">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <link rel="shortcut icon" href="{{asset('resources/images/favicon.ico')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('resources/css/toastr.css')}}"/>
    <link rel="stylesheet" type="text/css" href="{{asset('resources/css/style.css')}}"/>

    <title>TFDA::MIS 2.0</title>
</head>
<body>
<div id="loading-mask">
    <img class="center-img" alt="Loading..."
         src="<?php echo $base_url; ?>/resources/images/loader.gif"/>
</div>
<!--<div id="loading-parent">
    <div id="loading-child" class="loading-indicator">
      <center>
            <img height="150" class="center-img" alt="Spinner"
                 src="<?php echo $base_url; ?>/resources/images/loader.gif"/>
      </center>
    </div>
</div>-->
<script type="text/javascript">

    var token = document.querySelector('meta[name="csrf-token"]').content;
    var backendVersion = '{{ App::VERSION() }}';
    var is_logged_in = '<?php echo $is_logged_in; ?>';
    var is_reset_pwd = '<?php echo $is_reset_pwd; ?>';
    var guid = '<?php echo $guid; ?>';
    var user_id = '<?php echo $user_id; ?>';
    var title_id = '<?php echo $title_id; ?>';
    var gender_id = '<?php echo $gender_id; ?>';
    var profile_pic_url = '<?php echo $profile_pic_url; ?>';
    var first_name = '<?php echo $first_name; ?>';
    var last_name = '<?php echo $last_name; ?>';
    var fullnames = '<?php echo $title . ' ' . $first_name . ' ' . $last_name; ?>';
    var base_url = '<?php echo $base_url; ?>';
    var user_role_description = '<?php echo $access_point . ' - ' . $role; ?>';
    var email_address = '<?php echo $email; ?>';
    var phone_number = '<?php echo $phone; ?>';
    var mobile_number = '<?php echo $mobile; ?>';
    var dms_url = '<?php echo $base_url . '/mis_dms/'; ?>';

    var system_name = '<?php echo $system_name; ?>';
    var organisation_name = '<?php echo $organisation_name; ?>';
    var org_name = '<?php echo $org_name; ?>';
    var iso_cert = '<?php echo $iso_cert; ?>';
    var ministy_name = '<?php echo $ministy_name; ?>';
    var system_version = '<?php echo $system_version; ?>';
    var access_token = '<?php echo $access_token; ?>';

    var nonMenusArray = JSON.parse('<?php echo json_encode($nonMenusArray); ?>');

    var Ext = Ext || {}; // Ext namespace won't be defined yet...

    // This function is called by the Microloader after it has performed basic
    // device detection. The results are provided in the "tags" object. You can
    // use these tags here or even add custom tags. These can be used by platform
    // filters in your manifest or by platformConfig expressions in your app.
    //
    Ext.beforeLoad = function (tags) {
        var s = location.search,  // the query string (ex "?foo=1&bar")
            profile;

        // For testing look for "?classic" or "?modern" in the URL to override
        // device detection default.
        //
        if (s.match(/\bclassic\b/)) {
            profile = 'classic';
        }
        else if (s.match(/\bmodern\b/)) {
            profile = 'modern';
        }
        // uncomment this if you have added native build profiles to your app.json
        /*else if (tags.webview) {
            if (tags.ios) {
                profile = 'ios';
            }
            // add other native platforms here
        }*/
        else {
            //profile = tags.desktop ? 'classic' : 'modern';
            profile = tags.phone ? 'modern' : 'classic';
        }

        Ext.manifest = profile; // this name must match a build profile name

        // This function is called once the manifest is available but before
        // any data is pulled from it.
        //
        //return function (manifest) {
        // peek at / modify the manifest object
        //};
    };
</script>

<script type="text/javascript" src="{{asset('resources/js/jquery-3.1.1.js')}}"></script>
<script type="text/javascript" src="{{asset('resources/js/toastr.js')}}"></script>
<!-- The line below must be kept intact for Sencha Cmd to build your application -->
<script id="microloader" type="text/javascript" src="{{asset('bootstrap.js')}}"></script>
</body>
</html>
