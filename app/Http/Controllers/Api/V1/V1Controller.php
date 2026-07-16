<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class V1Controller extends Controller
{
    /**
     * @OA\PathItem (
     *     path ="/api/v1"
     * ),
     * @OA\Info(
     *      version="1.0.0",
     *      title="Management Api Documentation"
     * )
     */
    public function index()
    {
        //
    }
}
