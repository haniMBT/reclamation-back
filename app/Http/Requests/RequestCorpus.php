<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestCorpus extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if (isset($this->soustype2_document)) {
            $data = [
                'intitule_document' => 'required|unique:t_corpus_document',
                'nature_document' =>  'required',
                'type_document' =>  'required',
                'document_adopte' =>  'required',
                'soustype_document' =>  'required',
                'soustype2_document' =>  'required',
            ];

        } elseif (isset($this->soustype_document)) {
            $data = [
                'intitule_document' => 'required|unique:t_corpus_document',
                'nature_document' =>  'required',
                'type_document' =>  'required',
                'document_adopte' =>  'required',
                'soustype_document' =>  'required',
            ];

        } else {
            $data = [
                'intitule_document' => 'required|unique:t_corpus_document',
                'nature_document' =>  'required',
                'type_document' =>  'required',
                'document_adopte' =>  'required',
                // 'pdf_file' =>  'required',
            ];

        }
        // if (isset($this->piece_name)) {
        //     $data["piece_name"] = 'required|mimes:pdf';
        // }
        return $data;
    }
}
