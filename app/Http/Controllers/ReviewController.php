<?php

namespace App\Http\Controllers;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends HelperController
{
    public function store(Request $request)
    {
        $review = Review::create([
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'email' => $request->email,
            "name" => $request->name
        ]);
        return $this->sendResponse($review, 'product retrieved successfully.');
    }

}