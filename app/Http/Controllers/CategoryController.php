<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Str;

class CategoryController extends HelperController
{
    public function index()
    {
        $category = Category::all();
        return $this->sendResponse($category, 'Category retrieved successfully.');
    }
    public function store(Request $request)
    {
        // Validate request (optional but recommended)

        // Check if the category with the given name already exists
        $categoryExist = Category::where('name', $request->name)->exists();

        if ($categoryExist) {
            return $this->sendError('Category already exists', 400);
        }

        // Create new category instance
        $category = new Category();
        $category->name = $request->name;

        // Handle photo upload if present
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $image_name = date('d_m_y_') . Str::random(8) . "_" . time() . "_" . date('h_i_s') . "_" . Str::random(4) . "_" . rand(1111, 9999);
            $ext = strtolower($file->getClientOriginalExtension());
            $image_full_name = $image_name . '.' . $ext;
            $upload_path = public_path('images');
            $file->move($upload_path, $image_full_name);
            $category->photo = 'images/' . $image_full_name;
        }

        $category->save();

        return $this->sendResponse($category, 'Category created successfully.');
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendError('Category not found', 404);
        }
        return $this->sendResponse($category, 'Category retrieved successfully.');

    }
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendError('Category not found', 404);
        }
        if ($request->hasFile('photo')) {
            $file = $request->file(key: 'photo');
            $image_name = date('d_m_y_') . Str::random(8) . "_" . time() . "_" . date('h_i_s') . "_" . Str::random(4) . "_" . rand(1111, 9999);
            $ext = strtolower($file->getClientOriginalExtension());
            $image_full_name = $image_name . '.' . $ext;
            $upload_path = public_path('images'); // full path to public/images
            $file->move($upload_path, $image_full_name);

            // Save image path relative to public folder
            $category->photo = 'images/' . $image_full_name;
        }
        $category->name = $request->name;
        $category->save();
        return $this->sendResponse($category, 'Category updated successfully.');
    }
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendError('Category not found', 404);
        }
        $category->delete();
        return $this->sendResponse([], 'Category deleted successfully.');
    }
}