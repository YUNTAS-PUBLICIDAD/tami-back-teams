<?php
namespace App\Refactor\Services;

use App\Refactor\Models\Blog;

class BlogService
{
    public function create(array $data): Blog
    {
        return Blog::create($data);
    }

    public function update(Blog $blog, array $data): Blog
    {
        $blog->update($data);
        return $blog;
    }

    public function delete(Blog $blog): void
    {
        $blog->delete();
    }
}
