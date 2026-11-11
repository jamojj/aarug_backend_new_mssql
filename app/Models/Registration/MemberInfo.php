<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberInfo extends Model
{
    use HasFactory;
    protected $table = 'member_info';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'family_no',
        'member_no',
        'surname',
        'first_name',
        'other_names',
        'dob',
        'occupation',
        'id_pp_no',
        'relation_to_principal',
        'user_id',
        'date_entered',
        'family_title',
        'dealing_user',
        'cancelled',
        'employment_no',
        'gender',
        'card_to_member',
        'blood_group',
        'nhif_card_no',
        'height',
        'weight',
        'passport_no',
        'photo_n_form',
        'photo_no',
        'info_to_printer',
        'card_from_printer',
        'app_form_date',
        'card_serial_no',
        'corp_id',
        'family_size',
        'join_date',
        'title',
        'is_vip',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'hc_status',
        'kin_names',
        'kin_phone_no',
        'kin_email_address',
        'email',
        'tel_no',
        'chronic_member',
        'chronic_member_date_entered',
        'chronic_user',
        'chronic_diagnosis',
    ];
}
