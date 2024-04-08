<?php
namespace Simcify\Controllers;

use Simcify\Database;
use Simcify\Auth;

class sotietkiem1{

    /**
     * Get income page view
     * 
     * @return \Pecee\Http\Response
     */
    public function get() {
        $title = __('pages.sections.income');
        $user = Auth::user();
        $stats = array();
        $sotietkiems = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
        $sotietkiem = Database::table('sotietkiem')->where('user', $user->id)->orderBy("id", false)->get();
        $accounts = Database::table('accounts')->where('user', $user->id)->orderBy("id", false)->get();
        $categories = Database::table('categories')->where('user',$user->id)->where('type','expense')->orderBy("id", false)->get();
        $incomecategories = Database::table('categories')->where('user',$user->id)->where('type','Income')->orderBy("id", false)->get();
        $income = Database::table("income")->where("income`.`user", $user->id)->leftJoin("accounts", "income.account","accounts.id")->leftJoin("categories", "income.category","categories.id")->orderBy("income.id", false)->get("`income.id`", "`income.income_date`", "`income.category`", "`income.amount`","`income.danhba`", "`income.title`", "`accounts.name`", "`categories.name` as categoryname");
        $stats['earned'] = Database::table('income')->where('user', $user->id)->where('MONTH(`income_date`)', date("m"))->sum('amount','total')[0]->total;
        if ($user->monthly_earning > 0) {
          $stats['percentage'] = round(($stats['earned'] / $user->monthly_earning) * 100);
        }else{
          $stats['percentage'] = 0;
        }

        $totals = 0;
      if($sotietkiems->kyhan == 01 || $sotietkiems->kyhan == 02 ||$sotietkiems->kyhan == 03 ||$sotietkiems->kyhan == 04 ||$sotietkiems->kyhan == 05 ||$sotietkiems->kyhan == 06 ||$sotietkiems->kyhan == 07){
        $totals = ($sotietkiems->sodubandau * ($sotietkiems->laisuat/100)) + $sotietkiems->sodubandau;
      }else{
        $totals = ($sotietkiems->sodubandau * ($sotietkiems->laisuatkhongkyhan/100)) + $sotietkiems->sodubandau;
      }
        return view('sotietkiem1',compact("user","title","accounts","categories","incomecategories","income","stats", "sotietkiem","totals"));
    }

    /**
     * Account balance
     * 
     * @return true
     */
    public function balance($accountId, $amount, $action) {
      $account = Database::table('accounts')->where('id', $accountId)->first();
      if($action == 'tru'){
        $balance = $account->balance - $amount;
      }elseif($action == 'them'){
        $balance = $account->balance + $amount;
      }
      
      Database::table('accounts')->where('id', $accountId)->update(['balance' => $balance]);
      return true;
  }

    /**
     * Add Sổ tiết kiệm
     * 
     * @return Json
     */
    public function addSotietkiem1() {
      $user = Auth::user();
        $data = array(
          'user'=>$user->id,
          'tensotietkiem'=>escape(input('tensotietkiem')),
            'sodubandau'=>input('sodubandau'),
            'loaitiente'=>input('loaitiente'),
            'nganhang'=>input('nganhang'),
            'ngaygui'=>date('Y-m-d',strtotime(input('ngaygui'))),
            'kyhan'=>input('kyhan'),
            'laisuat'=>input('laisuat'),
            'laisuatkhongkyhan'=>input('laisuatkhongkyhan'),
            'songaytinhlaitrennam'=>input('songaytinhlaitrennam'),
            'tralai'=>input('tralai'),
            'khidenhan'=>input('khidenhan'),
            'account'=>input('account'),
            'diengiai'=>escape(input('diengiai'))
        );
        Database::table('sotietkiem')->insert($data);
        if (input('account') != "00") {
          self::balance(input('account'), input('amount'), "minus");
        }
        if (input('account') != "00") {
        $accountId = input('account');
        $amount = input('sodubandau'); // Số tiền của sổ tiết kiệm mới
        $action = 'tru';
        self::balance($accountId, $amount,$action);
        
    }
        return response()->json(responder("success", __('pages.messages.alright'), __('sotietkiem1.messages.add-success'), "reload()"));
    }

    


    /**
     * Sổ tiết kiệm update modal
     * 
     * @return \Pecee\Http\Response
     */
    public function updateview() {
      $user = Auth::user();

      $ngayMoi = date('Y-m-d');
      $date = date('Y-m-d', strtotime($ngayMoi . ' +1 day'));
      $accounts = Database::table('accounts')->where('user', $user->id)->orderBy("id", false)->get();
      $sotietkiem = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
      return view('includes/ajax/sotietkiem1',compact("sotietkiem","accounts","categories","date"));
  }

    /**
     * Update sổ tiết kiệm
     * 
     * @return Json
     */
    public function update(){
      $sotietkiem = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
      $user = Auth::user();
      $data = array(
          'user'=>$user->id,
          'tensotietkiem'=>escape(input('tensotietkiem')),
          'sodubandau'=>input('sodubandau'),
          'loaitiente'=>input('loaitiente'),
          'nganhang'=>input('nganhang'),
          'ngaygui'=>date('Y-m-d',strtotime(input('ngaygui'))),
          'kyhan'=>input('kyhan'),
          'laisuat'=>input('laisuat'),
          'laisuatkhongkyhan'=>input('laisuatkhongkyhan'),
          'songaytinhlaitrennam'=>input('songaytinhlaitrennam'),
          'tralai'=>input('tralai'),
          'khidenhan'=>input('khidenhan'),
          'account'=>input('account'),
          'diengiai'=>escape(input('diengiai'))
      );
     

    if (!empty($sotietkiem->account)) {
      self::balance($sotietkiem->account, $sotietkiem->sodubandau-input('sodubandau'), "them");
    }
    
      Database::table('sotietkiem')->where('id',input('sotietkiemid'))->update($data);
      return response()->json(responder("success", __('pages.messages.alright'), __('sotietkiem1.messages.edit-success'), "reload()"));
    }

    /**
     * Delete income record
     * 
     * @return Json
     */
    public function delete(){
      $sotietkiem = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
      if (!empty($sotietkiem->account)) {
        self::balance($sotietkiem->account, $sotietkiem->sodubandau, "them");
      }
      Database::table('sotietkiem')->where('id',input('sotietkiemid'))->delete();
      return response()->json(responder("success", __('pages.messages.alright'), __('sotietkiem1.messages.delete-success'), "reload()"));
    }

    /**
     * Sổ tiết kiệm update modal
     * 
     * @return \Pecee\Http\Response
     */
    public function tattoan() {
      $user = Auth::user();
      $accounts = Database::table('accounts')->where('user', $user->id)->orderBy("id", false)->get();
      $sotietkiem = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
      $account = Database::table('accounts')->where('id', input("accountid"))->first();
      $totals = 0;
      if($sotietkiem->kyhan == 01 || $sotietkiem->kyhan == 02 ||$sotietkiem->kyhan == 03 ||$sotietkiem->kyhan == 04 ||$sotietkiem->kyhan == 05 ||$sotietkiem->kyhan == 06 ||$sotietkiem->kyhan == 07){
        $totals = ($sotietkiem->sodubandau * ($sotietkiem->laisuat/100)) + $sotietkiem->sodubandau;
      }else{
        $totals = ($sotietkiem->sodubandau * ($sotietkiem->laisuatkhongkyhan/100)) + $sotietkiem->sodubandau;
      }

      return view('includes/ajax/sotietkiem2',compact("sotietkiem","account","accounts","totals"));
  }

  // $totals = 0;
  //     if($sotietkiem->kyhan == 01 || $sotietkiem->kyhan == 02 ||$sotietkiem->kyhan == 03 ||$sotietkiem->kyhan == 04 ||$sotietkiem->kyhan == 05 ||$sotietkiem->kyhan == 06 ||$sotietkiem->kyhan == 07){
  //       $totals = ($sotietkiem->sodubandau * ($sotietkiem->laisuat/100)) + $sotietkiem->sodubandau;
  //     }else{
  //       $totals = ($sotietkiem->sodubandau * ($sotietkiem->laisuatkhongkyhan/100)) + $sotietkiem->sodubandau;
  //     }
      

    /**
     * Tất toán
     * 
     * @return Json
     */
    public function tattoanluon(){
      $sotietkiem = Database::table('sotietkiem')->where('id', input("sotietkiemid"))->first();
      $ngayMoi = date('Y-m-d');
      $date = date('Y-m-d', strtotime($ngayMoi . ' +1 day'));
      if($sotietkiem->ngaygui <= $date){
        $lai = $sotietkiem->sodubandau * ($sotietkiem->laisuatkhongkyhan / 100)* (($date['d'] - $sotietkiem->songaytinhlaitrennam['d'])+11)/$sotietkiem->songaytinhlaitrennam;
        $sotienmoi = $sotietkiem->sodubandau + $lai; // Tổng số tiền mới sau khi tính lãi
      }else{
        $sotienmoi = $sotietkiem->sodubandau ; // Tổng số tiền mới sau khi tính lãi
      }
      
    
      if (!empty($sotietkiem->account)) {
          self::balance($sotietkiem->account, $sotienmoi, "them");
      }
      Database::table('sotietkiem')->where('id', input('sotietkiemid'))->delete();
      return response()->json(responder("success", __('pages.messages.alright'), __('sotietkiem1.messages.tattoan-success'), "reload()"));
  }



}
