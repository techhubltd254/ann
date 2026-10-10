<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
class PrivateRevenuePrivacyTest extends TestCase {
 public function test_private_allocation_is_mother_only():void {
  foreach(['kicc_admin'=>true,'national_admin'=>false,'county_admin'=>false,'institution_admin'=>false,'buyer'=>false] as $role=>$allowed){
   $u=\Mockery::mock(User::class)->makePartial();$u->status='active';$u->account_type='user';
   $u->shouldReceive('hasRole')->with('kicc_admin')->andReturn($role==='kicc_admin');
   $u->shouldReceive('hasRole')->with('superadmin')->andReturn(false);
   $this->assertSame($allowed,Gate::forUser($u)->allows('view-private-revenue'),$role);
  }
 }
 public function test_public_json_keeps_payable_total_but_removes_internal_allocations():void {
  $data=['buyer_total'=>103000,'product_price'=>100000,'feeBreakdown'=>['county_fee'=>800],'economics'=>['take_rate_pct'=>3],'items'=>[['commissionAmount'=>400,'total'=>5000]]];
  $this->assertSame(['buyer_total'=>103000,'product_price'=>100000,'items'=>[['total'=>5000]]],\App\Support\PrivateRevenue::redact($data));
 }
 public function test_public_bundle_does_not_embed_private_rates_or_finance_dashboards():void {
  $html=file_get_contents(resource_path('experience/reference-production.html'));
  foreach(['pipeline-fee-breakdown','pipeline-cascade','Producer net','County pipeline (0.80%)','National pool (0.60%)','take_rate_pct','fee_rate','pool_share'] as $needle)$this->assertStringNotContainsString($needle,$html);
 }
 public function test_public_component_is_server_side_empty():void {
  $this->assertSame('',trim(view('components.pipeline-fee-breakdown',['subtotal'=>100000])->render()));
 }
}
