<textarea v-validate="'{{$validations}}'" class="control" id="{{ $attribute->code }}" name="{{ $attribute->code }}"
          data-vv-as="&quot;{{ $attribute->admin_name }}&quot;">{{ old($attribute->code) ?: $product[$attribute->code]}}</textarea>
