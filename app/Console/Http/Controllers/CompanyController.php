<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Company;
use App\Website;
use App\User;
use App\CompanyUser;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Require Auth
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the default company and display it's settings
     */
    public function index( $id = null, Request $request )
    {
        if ( null !== $id ) {
            $company = Company::find( $id );
        } else {
            $company = $request->user()->activeCompany();
        }

        if ( null == $company ) {
            return redirect()->route('company.create');
        }

        return view( 'company.index', ['company' => $company, 'website' => $company->websites->first(), 'allCompanies' => $request->user()->companies] );
    }

    /**
     * Create
     */
    public function create()
    {
        return view( 'company.create' );
    }

    /**
     * Store default company
     */
    public function storeActive( Request $request )
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $userId = $request->user()->id;

        Cache::forever( "user:{$userId}:activeCompany", $request->input('company_id') );

        return back();
    }

    /**
     * Store company data
     */
    public function storeCompany( Request $request )
    {
        $request->validate([
            'name' => 'required',
            'website' => 'required|url',
        ]);

        $file_path = $this->storeFile($request);

        // dd($file_path);

        $company = ( $request->input('id') )
            ? Company::findOrFail( $request->input('id') )
            : new Company;

        $company->name = $request->input('name');
        
        if('' != $file_path)
            $company->image = $file_path;

        $company->save();

        if ( $company->users->isEmpty() ) {
            $request->user()->companies()->save( $company, ['role' => 'admin'] );
        }

        // Store url as website data (for now)
        $website = ( $company->websites->isNotEmpty() )
            ? $company->websites->first()
            : new Website;

        $website->name = $request->input('name');
        $website->url  = $request->input('website');

        $company->websites()->save( $website );

        return redirect()->route('company', ['id' => $company->id]);
    }

    /**
     * Store the image of the company into storage > public > company_image folder
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function storeFile(Request $request)
    {
        $file_path = '';
        if ($request->hasFile('image')) {
            $file_path = $request->file('image')->store('public/company_image');
            $file_path = str_replace("public/", "", $file_path);
        }
        return $file_path;
    }

    /**
     * Add New User in Company
     */

    public function addUser(Request $request)
    {
        $currentCompany = $request->user()->activeCompany();

        $user = new User;
        $this->validate($request,[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user ->name = $request->name;
        $user ->email = $request->email;
        $user ->password = bcrypt($request->password);

        if($user->save()){
            $companyUser = new CompanyUser;
            $companyUser->company_id = (!empty($currentCompany) && isset($currentCompany['id']))?$currentCompany['id']:$request->company_id;
            $companyUser->user_id = $user->id;
            $companyUser->role = 'admin';
            $companyUser->save();
            return back()
                ->with('success','User Added successfully.');
        }else{
            return back()
                ->with('failure','Something went wrong. Please try later!.');
        }

    }

    public function editUser($id = null,Request $request )
    {
        if($id != null && $id > 0){
            $user = User::find($id);
            return view('company.edit-user', ['user' => $user]);
        }
        return view('company.edit-user', ['user' => $request->user()]);
    }

    /**
     * Change User Status For Company Listing
     */

    public function changeStatus($id)
    {
        $user = User::find($id);
        if($user)
        {
            if($user->status=="active")
                $user->status = "inactive";
            else
                $user->status = "active";

            if($user->save())
                session()->flash('success', "User's status has been changed successfully!");
            else
                session()->flash('failure', 'System failed to change the status of this user!');
        }
        else
        {
            session()->flash('failure', "User doesn't exist in the system!");
        }

        return back();
    }
}
