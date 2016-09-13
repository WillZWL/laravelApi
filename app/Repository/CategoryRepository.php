<?php

namespace App\Repository;

use App\Models\Category;

class CategoryRepository
{
    public function all()
    {
        return Category::where('status', 1)->get();
    }
}