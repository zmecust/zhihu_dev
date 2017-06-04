<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Topic;
use Illuminate\Http\Request;

class TopicsController extends Controller
{
    public function index(Request $request) {
        $topics = Topic::select(['id', 'name'])->where('name', 'like', '%'.$request->query('q').'%')->get();
        /*foreach ($topics as $topic) {
            $data = [
                'name' => $topic->name,
                'value' => $topic->name,
                'text' => $topic->name,
            ];
        }
        return response()->json([
            'success' => 'true',
            'results' => [$data]
            ]);*/

        return $this->responseSuccess('查询成功', $topics->toArray());
    }
}
