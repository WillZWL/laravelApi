<?php

namespace App\Repository;

use App\Models\Category;

class CategoryRepository
{
    public function all()
    {
        return Category::whereStatus(1)->get();
    }
}