<?php

namespace App\Traits;

trait ResponseTrait {

    public function successResponse($label, $data) {
        return response()->json([
            'isSuccess' => true,
            'data' => [
                $label => $data,
            ]
        ]);
    }

    public function errorResponse($data)
    {
        return response()->json([
            'isSuccess' => false,
            'errorText' => $data,
        ]);
    }
}