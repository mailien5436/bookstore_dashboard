<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'phone' => [
                'required',
                'unique:customers,phone,' . $this->request->get('id'),
                'regex:/^0\d{9}$/',
            ],
            'email' => [
                'required',
                'unique:customers,email,' . $this->request->get('id'),
                'email:rfc,dns',
            ],
            'address' => 'required',
            'avatar' => [
                $this->has('avatar') ? 'image' : '',
                $this->has('avatar') ? 'max:2048' : '',
            ]
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
            'phone.regex' => 'Số điện thoại chỉ được chứa ký tự số, bắt đầu bằng số 0 và đủ 10 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã tồn tại.',
            'email.email' => 'Sai định dạng email.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'avatar.image' => 'File phải là hình ảnh.',
            'avatar.max' => 'Kích thước hình ảnh không được vượt quá 2 MB.',
        ];
    }
}
