<?php

namespace App\Models;

use App\Notifications\SubscribeNotification;
use App\Services\GoogleSheet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Subscribe extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function booted()
    {
        static::created(function($subscribe) {
            $created_at = Carbon::parse($subscribe->created_at)->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
            $created_at_formatted = Carbon::parse($subscribe->created_at)->timezone('Asia/Riyadh')->format('Y-m-d');

            $image_path = '-';
            if($subscribe->money_transfer_image_path){
                $image_path = url(Storage::url($subscribe->money_transfer_image_path));
            }

            $relation = 'student';
            if ($subscribe->form_type == 'stopped-students'){
                $relation = 'student';
            }

            if ($subscribe->form_type == 'new-students'){
                $relation = 'newStudent';
                $subscribe->newStudent['name'] = $subscribe->{$relation}->first_name . ' ' . $subscribe->{$relation}->father_name . ' ' . $subscribe->{$relation}->grandfather_name . ' ' . $subscribe->{$relation}->family_name;
            }

            $googleSheet = new GoogleSheet();
            $values = [
                [
                    $created_at  ?? '-', $subscribe->reference_number  ?? '-', $created_at_formatted ?? '-',
                    'أقرّ باطلاعي نظام التعليم عن بعد الخاص بالمركز.', 'نعم',
                    $subscribe->{$relation}->section == 1 ? 'بنين' : 'بنات', $subscribe->{$relation}->serial_number ?? '-',
                    $subscribe->{$relation}->name ?? '-', $subscribe->country->name, $subscribe->email,
                    $image_path ?? '-', $subscribe->bank_name ?? '-', $subscribe->account_owner ?? '-',
                    $subscribe->transfer_date ?? '-', $subscribe->bank_reference_number ?? '-', $subscribe->payment_method ?? '-',
                    $subscribe->payment_id ?? '-', $subscribe->payment_status ?? '-', $subscribe->response_code ?? '-', $subscribe->coupon_code ?? '-', ($subscribe->discount_value/100) ?? '0.0'
                ],
            ];

            $googleSheet->saveDataToSheet($values);

            if ($subscribe->payment_method == 'checkout_gateway' && is_numeric($subscribe->response_code) && in_array($subscribe->payment_status, ['Captured', 'Authorized']) ){
                Notification::route('mail', [$subscribe->email])->notify(new SubscribeNotification($subscribe));
            }

            if ($subscribe->payment_method == 'hsbc'){
                Notification::route('mail', [$subscribe->email])->notify(new SubscribeNotification($subscribe));
            }

        });

        static::updated(function($subscribe) {

            $relation = 'student';
            if ($subscribe->form_type == 'stopped-students'){
                $relation = 'student';
            }

            if ($subscribe->form_type == 'new-students'){
                $relation = 'newStudent';
                $subscribe->newStudent['name'] = $subscribe->{$relation}->first_name . ' ' . $subscribe->{$relation}->father_name . ' ' . $subscribe->{$relation}->grandfather_name . ' ' . $subscribe->{$relation}->family_name;
            }

            if ($subscribe->payment_method == 'checkout_gateway'){
                $created_at = Carbon::parse($subscribe->created_at)->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
                $created_at_formatted = Carbon::parse($subscribe->created_at)->timezone('Asia/Riyadh')->format('Y-m-d');

                $image_path = '-';
                if($subscribe->money_transfer_image_path){
                    $image_path = url(Storage::url($subscribe->money_transfer_image_path));
                }

                $googleSheet = new GoogleSheet();
                $values = [
                    [
                        $created_at  ?? '-', $subscribe->reference_number  ?? '-', $created_at_formatted ?? '-',
                        'أقرّ باطلاعي نظام التعليم عن بعد الخاص بالمركز.', 'نعم',
                        $subscribe->{$relation}->section == 1 ? 'بنين' : 'بنات', $subscribe->{$relation}->serial_number ?? '-',
                        $subscribe->{$relation}->name ?? '-', $subscribe->country->name, $subscribe->email,
                        $image_path, $subscribe->bank_name ?? '-', $subscribe->account_owner ?? '-',
                        $subscribe->transfer_date ?? '-', $subscribe->bank_reference_number ?? '-', $subscribe->payment_method ?? '-',
                        $subscribe->payment_id ?? '-', $subscribe->payment_status ?? '-', $subscribe->response_code ?? '-', $subscribe->coupon_code ?? '-', ($subscribe->discount_value/100) ?? '0.0'
                    ],
                ];

                $googleSheet->saveDataToSheet($values);

                if (is_numeric($subscribe->response_code) && in_array($subscribe->payment_status, ['Captured', 'Authorized']) ){
                    Notification::route('mail', [$subscribe->email])->notify(new SubscribeNotification($subscribe));
                }
            }
        });

    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function newStudent()
    {
        return $this->belongsTo(NewStudent::class, 'new_student_id');
    }

    public function stoppedStudent()
    {
        return $this->belongsTo(StoppedStudent::class);
    }

}
