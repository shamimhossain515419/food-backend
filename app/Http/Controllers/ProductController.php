<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends HelperController
{

    public function __construct()
    {
        date_default_timezone_set("Asia/Dhaka");
    }

    public function index()
    {
        $products = Product::all();
        return $this->sendResponse($products, 'product retrieved successfully.');
    }

    public function getCategoryWiseProduct(Request $request)
    {
        $category_id = $request->input('category_id');

        if (!$category_id) {
            return $this->sendError('Category ID is required', [], 400);
        }

        // Try to find the category
        $category = Category::find($category_id);

        if (!$category) {
            return $this->sendError('Category not found', [], 404);
        }

        // Get products for the category
        $products = Product::where('category_id', $category_id)->get();
        $data = [
            'category' => $category,
            'products' => $products,
        ];

        return $this->sendResponse($data, 'Products retrieved successfully.');
    }


    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->quantity = $request->quantity;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->category_id = $request->category_id;

        if ($request->hasFile('photo')) {
            $file = $request->file(key: 'photo');
            $image_name = date('d_m_y_') . Str::random(8) . "_" . time() . "_" . date('h_i_s') . "_" . Str::random(4) . "_" . rand(1111, 9999);
            $ext = strtolower($file->getClientOriginalExtension());
            $image_full_name = $image_name . '.' . $ext;
            $upload_path = public_path('images'); // full path to public/images
            $file->move($upload_path, $image_full_name);

            // Save image path relative to public folder
            $product->photo = 'images/' . $image_full_name;
        }

        $product->save();

        return $this->sendResponse($product, 'Product created successfully.');
    }


    public function show(Request $request, $id)
    {
        $product = Product::where("id", $id)->with('category')->first();
        if (!$product) {
            return $this->sendError('product not found', 404);
        }
        $categories = [];
        if ($request->category_id) {
            $categories = Product::where('category_id', $product->category_id)->whereNot('id', $product->id)->get();
        }
        $reviews = Review::where('product_id', $product->id)->get();
        return $this->sendResponse([
            "products" => $product,
            "categories" => $categories,
            "reviews" => $reviews
        ], 'product retrieved successfully.');

    }
    public function updateProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->sendError('Product not found', 404);
        }

        // Update individual fields only if they exist in the request
        $product->name = $request->name ?? $product->name;
        $product->price = $request->price ?? $product->price;
        $product->description = $request->description ?? $product->description;
        $product->quantity = $request->quantity ?? $product->quantity;
        $product->category_id = $request->category_id ?? $product->category_id;

        // Handle image upload
        if ($request->hasFile('photo')) {
            $file = $request->file(key: 'photo');
            $image_name = date('d_m_y_') . Str::random(8) . "_" . time() . "_" . date('h_i_s') . "_" . Str::random(4) . "_" . rand(1111, 9999);
            $ext = strtolower($file->getClientOriginalExtension());
            $image_full_name = $image_name . '.' . $ext;
            $upload_path = public_path('images'); // full path to public/images
            $file->move($upload_path, $image_full_name);

            // Save image path relative to public folder
            $product->photo = 'images/' . $image_full_name;
        }

        $product->save();

        return $this->sendResponse($product, 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = product::find($id);
        if (!$product) {
            return $this->sendError('product not found', 404);
        }
        $product->delete();
        return $this->sendResponse([], 'product deleted successfully.');
    }


    public function guid()
    {
        $hostname = "food";
        return $hostname . '-' . date('Y-m-d-H-i-s') . '-' . Str::random(12) . '-' . rand(1111, 9999) . '-' . Str::random(16);
    }

    protected function deleteOldPhoto($photoPath)
    {
        if ($photoPath) {
            $filePath = public_path('storage/images/' . $photoPath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}