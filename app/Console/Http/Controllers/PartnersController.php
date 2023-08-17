<?php

namespace App\Http\Controllers;

use Auth;
use Storage;
use Dompdf\Dompdf;
use App\Partner;
use App\PartnerData;
use App\PartnerLink;
use App\PartnerPost;
use App\PartnerContact;
use App\PartnerDoc;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

class PartnersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
    }

    /**
     * Render sponsors list
     */
    public function sponsors( Request $request )
    {
        $company = $request->user()->activeCompany();
        $partners = $company->getSponsors();

        if ( $request->input('order') == 'active' ) {
            //$partners = $partners->sortBy('status');
            $partners = $partners->where('status',0)->all();
        }

        if ( $request->input('order') == 'inactive' ) {
            //$partners = $partners->sortBy('status');
            $partners = $partners->where('status',1)->all();
        }

        if ( $request->input('order') == 'date' ) {
            $partners =  $partners->sortBy('created_at');
        }

        return view( 'partners.sponsors', ['partners' => $partners] );
    }

    /**
     * Render affiliates list
     */
    public function affiliates( Request $request )
    {
        $company = $request->user()->activeCompany();
        $partners = $company->getAffiliates();

        if ( $request->input('order') == 'active' ) {
            //$partners = $partners->sortBy('status');
            $partners = $partners->where('status',0)->all();

        }

        if ( $request->input('order') == 'inactive' ) {
            //$partners = $partners->sortBy('status');
            $partners = $partners->where('status',1)->all();
        }

        if ( $request->input('order') == 'date' ) {
            $partners =  $partners->sortBy('created_at');
        }

        return view( 'partners.affiliates', ['partners' => $partners] );
    }

    /**
     * Store partner data
     */
    public function store( Request $request )
    {
        $company = $request->user()->activeCompany();
        $partner = ( $request->input('id') ) ? Partner::firstOrFail( $request->input('id') ) : new Partner;

        $partner->company_id = $company->id;
        $partner->status = false;
        $partner->name = trim($request->input('name'));
        $partner->type = $request->input('type');

        $partner->save();

        if ( $request->input('type') === 'sponsor' ) {
            $dataKeys = ['campaign', 'payment', 'summary', 'posts'];
        } else if ( $request->input('type') === 'affiliate' ) {
            $dataKeys = ['product', 'payment_type', 'payment_amount', 'summary', 'payment_schedule', 'affiliate_link', 'coupon_code'];
        }

        foreach ( $dataKeys as $key ) {
            if ( $request->has( $key ) && $request->input( $key ) !== null ) {
                $meta = PartnerData::updateOrCreate(
                    [
                        'partner_id' => $partner->id,
                        'name' => $key
                    ],
                    ['value' => $request->input( $key )]
                );
            }
        }

        if ( $request->has('contact') ) {
            foreach ( $request->input('contact') as $input ) {
                $contact = new PartnerContact;
                
                $contact->partner_id = $partner->id;
                $contact->name       = $input['name'];
                $contact->email      = $input['email'];
                if(!empty($input['email'])){
                    $contact->save();
                }

            }
        }

        return back();
    }

    /**
     * Store partner status
     */
    public function storeStatus( Request $request )
    {
        $partner = Partner::findOrFail( $request->input('id') );

        $partner->status = ( $request->has('status') ) ? true : false;
        $partner->save();

        return back();
    }

    /**
     * Delete partner
     */
    public function delete( Request $request )
    {
        $request->validate([
            'id' => 'required|exists:partners,id'
        ]);

        Partner::destroy( $request->input('id') );

        return back()->with('success', 'Partner deleted successfully!');
    }

    /**
     * Store contact for partner
     */
    public function storeContact( Request $request )
    {
        $prevRoute = $request->input('route');

        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'name'       => 'required|max:60',
            'email'      => 'required|email'
        ]);

        $contact = new PartnerContact;
    
        $contact->partner_id = $request->input('partner_id');
        $contact->name       = $request->input('name');
        $contact->email      = $request->input('email');

        $contact->save();

        return redirect()->route($prevRoute,['partner_id'=>$request->input('partner_id')])->with('success', 'Saved successfully!');
        //return back();
    }

    /**
     * Delete contact for partner
     */
    public function deleteContact( Request $request )
    {
        PartnerContact::destroy( $request->input('id') );

        return back()->with('success', 'Contact deleted successfully!');
    }

    /**
     * Store link
     */
    public function storeLink( Request $request )
    {
        $prevRoute = $request->input('route');

        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'name'       => 'required',
            'url'        => 'required',
            'post_id'    => 'required',
            'type'       => 'required',
        ]);

        $link = new PartnerLink;
        $link->partner_id = $request->input('partner_id');
        $link->type       = $request->input('type');
        $link->name       = $request->input('name');
        $link->url        = $request->input('url');
        $link->post_id    = $request->input('post_id');

        // Make sure the post is a valid ID
        $response = $link->checkCounts($request->input('type'));

        if (false == $response) {
            return back()->withErrors(['danger'=>'Couldnt connect to post, please verify the post ID is correct and try again']);
        }

        if($response === "no_account"){
            $type = $request->input('type');
            $url = route($type);
            return back()->withErrors(['danger'=>"Please connect your <a href='".$url."' target='_blank'>".$request->input('type')."</a> account."]);
        }

        $link->save();

        return redirect()->route($prevRoute,['partner_id'=>$request->input('partner_id')])->with('success', 'Saved successfully!');
    }

    /**
     * Delete link
     */
    public function deleteLink( Request $request ) {
        $prevRoute = $request->input('route');
        $pid = $request->input('pid');

        $link = PartnerLink::findOrFail( $request->input('id') );

        $link->delete();

        if($pid > 0){
            return redirect()->route($prevRoute,['partner_id'=>$pid])->with('success', 'Link removed successfully!');
        }else{
            return back()->with('success', 'Link removed successfully!');
        }
    }

    /**
     * Store post
     */
    public function storePost( Request $request )
    {
        $prevRoute = $request->input('route');

        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'name' => 'required',
        ]);

        $post = new PartnerPost;
        $post->partner_id = $request->input('partner_id');
        $post->name = $request->input('name');
        
        if ( $request->has('draft_due') ) {
            $post->draft_due = $request->input('draft_due');
        }

        if ( $request->has('final_due') ) {
            $post->final_due = $request->input('final_due');
        }

        $post->save();

        return redirect()->route($prevRoute,['partner_id'=>$request->input('partner_id')])->with('success', 'Saved successfully!');

        //return back()->with('success', 'Saved successfully!');
    }

    /**
     * Store meta data for partner
     */
    public function storeData( Request $request )
    {
        $partner = Partner::where('id',$request->input('id'))->first();

        $key = $request->input('name');
        $value = $request->input( $key );

        if(empty($value)){
            session()->flash('failure', 'Please provide '.$key.' value.');
            return back();
        }

        PartnerData::updateOrCreate(
            [
                'partner_id' => $partner->id,
                'name' => $key
            ],
            ['value' => $request->input( $key )]
        );
        if ($request->has('route') && $request->has('id')) {
            $prevRoute = $request->input('route');

            return redirect()->route($prevRoute,['partner_id'=>$request->input('id')])->with('success', 'Saved successfully!');

        }else{
            return back()->with('success', 'Saved successfully!');
        }
    }

    /**
     * Store document
     */
    public function storeDoc( Request $request )
    {
        $prevRoute = $request->input('route');
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'name' => 'required',
            'doc' => 'required|file|mimes:gif,jpeg,jpg,bmp,png,docx,doc,pdf,csv,txt|max:20000'
        ]);

        //$path = $request->file('doc')->store('docs');
        $extension = $request->file('doc')->getClientOriginalExtension();
        $filename = uniqid().'.'.$extension;
        $path = Storage::disk('local')->putFileAs('/docs/', $request->file('doc'), $filename);

        $doc = new PartnerDoc;

        $doc->partner_id = $request->input('partner_id');
        $doc->name = ( $request->has('name') ) ? $request->input('name') : basename( $path );
        $doc->path = $path;
        $doc->save();

        return redirect()->route($prevRoute,['partner_id'=>$request->input('partner_id')])->with('success', 'Doc successfully uploaded!');

        //return back()->with('success', 'Doc successfully uploaded');
    }

    /**
     * Generate PDF
     */
    public function generatePDF( Request $request )
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id'
        ]);

        $company = $request->user()->activeCompany();
        $partner = Partner::findOrFail( $request->input('partner_id') );

        $facebook  = [];
        $youtube   = [];
        $twitter   = [];
        $pinterest = [];
        $instagram = [];

        foreach ($partner->links as $link){
            if('facebook' == $link->type){
                $facebook[] = $link;
            }
            elseif('youtube' == $link->type){
                $youtube[] = $link;
            }
            elseif('twitter' == $link->type){
                $twitter[] = $link;
            }
            elseif('pinterest' == $link->type){
                $pinterest[] = $link;
            }
            elseif('instagram' == $link->type){
                $instagram[] = $link;
            }
        }

        $html = view('partners.partials.report-pdf', ['partner' => $partner, 'company' => $company, 'facebook' => $facebook, 'youtube' => $youtube, 'twitter' => $twitter, 'pinterest' => $pinterest, 'instagram' => $instagram]);

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $pdf = new Mpdf([
            'fontDir' => array_merge($fontDirs, [
                public_path('font-awesome/fonts'),
            ]),
            'fontdata' => $fontData + [
                'fontawesome' => [
                    'R' => "fontawesome-webfont.ttf",
                ]
            ],
            'default_font' => 'fontawesome',
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 5,
        ]);

        $pdf->WriteHTML(file_get_contents(asset('css/pdf.css')), 1);
        $pdfFilePath = "Reports.pdf";
        $pdf->SetTitle("Report PDF");

        $pdf->SetDisplayMode('fullpage');

        $pdf->WriteHTML($html,2);
        $pdf->Output($pdfFilePath, "I");
        exit();
    }
}
