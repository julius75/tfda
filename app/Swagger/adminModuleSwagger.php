<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 9/7/2018
 * Time: 1:35 PM
 */
/**
 * @OA\GET(
 *     path="/administration/getAdminParamFromModel",
 *     tags={"Administration"},
 *     summary="Get Administration Module Parameters",
 *     operationId="getAdminParamFromModel",
 *     description=" ",
 *     @OA\Parameter(
 *          name="model_name",
 *          description="Name of the backend model class",
 *          required=true,
 *          in="query",
 *          @OA\Schema(
 *              type="string"
 *          )
 *      ),
 *     @OA\Parameter(
 *          name="strict_mode",
 *          description="Check for disabled records",
 *          required=false,
 *          in="query",
 *          @OA\Schema(
 *              type="integer"
 *          )
 *      ),
 *     @OA\Response(
 *          response="200",
 *          description="Records fetched successfully"
 *      ),
 *     @OA\Response(
 *          response="default",
 *          description="an ""unexpected"" error"
 *      )
 * )
 */