<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\CouponRepositoryInterface;
use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Coupon CRUD carries no business workflow, so it works on the model directly.
 */
final class CouponController extends Controller
{
    public function index(Request $request, CouponRepositoryInterface $coupons): Response
    {
        $search = $this->searchTerm($request);

        return Inertia::render('admin/coupons', [
            'coupons' => CouponResource::collection($coupons->paginateForAdmin($search)),
            'filters' => ['search' => $search],
            'types'   => CouponType::options(),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::query()->create($request->couponAttributes());

        $this->toast(__('Coupon :code created.', ['code' => $coupon->code]));

        return back();
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->couponAttributes());

        $this->toast(__('Coupon :code updated.', ['code' => $coupon->code]));

        return back();
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        // Orders keep their own copy of the code; coupon_id is nulled on delete.
        $coupon->delete();

        $this->toast(__('Coupon :code deleted.', ['code' => $coupon->code]));

        return back();
    }
}
