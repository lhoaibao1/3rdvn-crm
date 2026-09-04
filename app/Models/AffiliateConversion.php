<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateConversion extends Model
{
    protected $fillable=[
        'partner','conversion_id','transaction_id','click_id','offer_id','campaign_name',
        'conversion_status','conversion_status_code','sale_amount','publisher_payout',
        'click_time','conversion_time','conversion_modified_time','conversion_status_updated_time',
        'product_name','product_url','product_sku','product_category_id','product_category',
        'aff_sub1','aff_sub2','aff_sub3','aff_sub4','landing_page','event','status_message',
        'shop_status_code','publisher_id','conversion_date','conversion_modified_date','click_date',
        'created_by_id','raw_payload'
    ];
    
    protected $hidden=['publisher_payout'];
    
    protected function casts():array
    {
        return[
            'sale_amount'=>'decimal:2',
            'publisher_payout'=>'decimal:2',
            'click_time'=>'datetime',
            'conversion_time'=>'datetime',
            'conversion_modified_time'=>'datetime',
            'conversion_status_updated_time'=>'datetime',
            'raw_payload'=>'array'
        ];
    }
    
    public function toArray()
    {
        $array = parent::toArray();
        
        // Show publisher_payout only to admins
        if (!$this->isAdminViewing()) {
            unset($array['publisher_payout']);
        }
        
        return $array;
    }
    
    /**
     * Check if current user is admin viewing this
     */
    private function isAdminViewing(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        
        // Check if user has admin role or is super admin
        return $user->hasRole(['admin', 'super_admin']) || 
               ($user->hasPermissionTo ? $user->hasPermissionTo('view_payout') : false);
    }
    
    public function createdBy():BelongsTo
    {
        return $this->belongsTo(User::class,'created_by_id');
    }
}
