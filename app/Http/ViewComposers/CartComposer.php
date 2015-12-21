<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/8/25
 * Time: 20:51
 */
namespace App\Http\ViewComposers;

use App\Models\Category;
use App\Models\Node;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;

class CartComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('cartNum', auth()->user()->carts->count());
    }

}
