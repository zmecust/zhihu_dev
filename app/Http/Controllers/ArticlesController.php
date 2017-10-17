<?php

namespace App\Http\Controllers;

use App\Article;
use App\Category;
use App\Http\Requests\StoreArticleRequest;
use Cache;
use Auth;
use Validator;
use App\Repositories\ArticleRepository;
use Illuminate\Http\Request;

class ArticlesController extends Controller
{
    /**
     * @var ArticleRepository
     */
    protected $articleRepository;

    /**
     * @var StoreArticleRequest
     */
    protected $storeArticleRequest;

    /**
     * ArticlesController constructor.
     * @param ArticleRepository $articleRepository
     * @param StoreArticleRequest $storeArticleRequest
     */
    public function __construct(ArticleRepository $articleRepository, StoreArticleRequest $storeArticleRequest)
    {
        $this->articleRepository = $articleRepository;
        $this->storeArticleRequest = $storeArticleRequest;
        $this->middleware('jwt.auth', [
            'only' => ['store', 'update', 'destroy']
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = 1;

        if ($request->input('page')) {
            $page = $request->input('page');
        }

        $articles = $this->articleRepository->getArticles($page, $request);

        if (! empty($articles)) {
            return $this->responseSuccess('OK', $articles);
        }

        return $this->responseError('查询失败');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @param StoreArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreArticleRequest $request)
    {
        $tags = $this->articleRepository->normalizeTopics($request->get('tag'));
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id(),
            'is_hidden' => $request->get('is_hidden'),
            'category_id' => $request->get('category'),
        ];
        $article = $this->articleRepository->create($data);
        $article->increment('category_id');
        Category::find($request->get('category'))->increment('articles_count');
        Auth::user()->increment('articles_count');
        $article->tags()->attach($tags);
        Cache::tags('articles')->flush();
        return $this->responseSuccess('OK', $article);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $article = $this->articleRepository->getArticle($id);

        if (! empty($article)) {
            return $this->responseSuccess('OK', $article->toArray());
        }

        return $this->responseError('查询失败');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * @param StoreArticleRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreArticleRequest $request, $id)
    {
        $tags = $this->articleRepository->normalizeTopics($request->get('tag'));
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'is_hidden' => $request->get('is_hidden'),
            'category_id' => $request->get('category'),
        ];
        $article = $this->articleRepository->byId($id);
        $article->update($data);
        $article->tags()->sync($tags);
        Cache::tags('articles')->flush();
        return $this->responseSuccess('OK', $article);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function hotArticles()
    {
        if (empty($hotArticles = Cache::get('hotArticles_cache'))) {
            $hotArticles = Article::where([])->orderBy('comments_count', 'desc')->latest('updated_at')->take(10)->get();
            Cache::put('hotArticles_cache', $hotArticles, 10);
        }
        return $this->responseSuccess('查询成功', $hotArticles);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function contentImage(Request $request)
    {
        $file = $request->file('image');
        $filename = md5(time()) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('../storage/app/public/articleImage'), $filename);
        $article_image = env('API_URL') . '/storage/articleImage/'.$filename;
        return $this->responseSuccess('查询成功', ['url' => $article_image]);
    }

}