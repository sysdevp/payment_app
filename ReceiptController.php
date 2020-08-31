<?php
namespace App\Http\Controllers;
ini_set('memory_limit','-1');

use App\Models\Receipt;
use App\Models\ChitDetail;
use App\Models\BiddingDetail;
use App\Models\CommitmentAuction;
use App\Models\Employee;
use App\Models\AccountExpense;
use App\Models\AccountHead;
use App\Models\Scheme;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Customer;
use App\Models\AdvanceReceipt;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\AssetDetail;
use App\Models\BankDetail;
use App\Models\PaymentType;
use App\Models\SlabDue;
use App\Models\UpdateBranch;
use App\Models\UpdateGroup;
use App\Models\UpdateCustomer;
use App\Models\UnknownTransaction;
use App\Models\AuctionOnHoliday;
use App\Models\LogMonitoringTable;
use App\Models\Payment;
use App\Models\AgentCommissionPayment;
use App\Models\OtherCharge;
use App\Models\LeadManagement;
use App\Models\User;
use Illuminate\Http\Request;
use DateTime;
use Validator;
use DB; 
use Illuminate\Support\Facades\Storage;
use Excel;

class ReceiptController extends Controller
{
	public function store_receipt(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required',
            'branch_id' => 'required',
            'customer_id' => 'required',
            //'group_id' => 'required',
			'employee_id' => 'required',
			'enrollment_id'=> 'required',
			//'receipt_type'=>'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
				if(isset($request->Installments)) 
				{  
					$count = count($request->Installments);
					$receipt_no = '';
					for($i=0; $i<$count; $i++)
					{	
						$receipt = new Receipt(); 
						$receipt->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
						$receipt->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
						$receipt->other_branch = isset($request->other_branch) ? ($request->other_branch) : 0;
						$receipt->customer_id = isset($request->customer_id) ? ($request->customer_id) : 0;
						$receipt->enrollment_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
						$receipt->group_id = isset($request->group_id) ? ($request->group_id) : 0;
						$receipt->ticket_no = isset($request->ticket_no) ? ($request->ticket_no) : '';
						$receipt->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0;
						$employee_branch_id = Employee::where('id','=',$receipt->employee_id)->pluck('branch_id');
						$receipt->employee_branch_id = $employee_branch_id[0];
						$receipt->adjust_id = isset($request->adjust_id) ? ($request->adjust_id) : 0;
						$receipt->amount = isset($request->amount) ? ($request->amount) : 0;
						if(isset($request->receipt_date)) {
							$r_date = ($request->receipt_date['year']).'-'.sprintf('%02d',($request->receipt_date['month'])).'-'.sprintf('%02d',($request->receipt_date['day'])); 
							$receipt->receipt_date = date('Y-m-d',strtotime($r_date)); 
						}
						else {
							$receipt->receipt_date = '0000-00-00';
						}
						//$receipt->receipt_date = isset($request->receipt_date) ? ($request->receipt_date) : "0000-00-00";
						$receipt->type_of_collection = 'receipt';
						if($request->receipt_type == 'Advanced Receipt') {
							$receipt->payment_type_id = 7;
							$receipt->type_of_collection = 'advance_receipt';
						}
						else if($request->receipt_type == 'Before Enrol Receipt') {
							$receipt->payment_type_id = 8;
							$receipt->type_of_collection = 'before_enrol_receipt';
						}
						else {
						$receipt->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;
						$receipt->type_of_collection = 'receipt';
						}
						$receipt->debit_to	 = isset($request->debit_to	) ? ($request->debit_to	) : '';
						$receipt->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : '';
						if(isset($request->cheque_date)) {
							$c_date = ($request->cheque_date['year']).'-'.sprintf('%02d',($request->cheque_date['month'])).'-'.sprintf('%02d',($request->cheque_date['day'])); 
							$receipt->cheque_date = date('Y-m-d',strtotime($c_date)); 
						}
						else {
							$receipt->cheque_date = '0000-00-00';
						}
						//$receipt->cheque_date = isset($request->cheque_date) ? ($request->cheque_date) : "0000-00-00";
						if(isset($request->cheque_clear_return_date)) {
							$cc_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
							$receipt->cheque_clear_return_date = date('Y-m-d',strtotime($cc_date)); 
						}
						else {
							$receipt->cheque_clear_return_date = '0000-00-00';
						}
						//$receipt->cheque_clear_return_date = isset($request->cheque_clear_return_date) ? ($request->cheque_clear_return_date) : "0000-00-00";
						$receipt->bank_name_id = isset($request->bank_name_id) ? ($request->bank_name_id) : 0;
						$receipt->branch_name = isset($request->branch_name) ? ($request->branch_name) : '';
						$receipt->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0;
						if(isset($request->transaction_date)) {
							$t_date = ($request->transaction_date['year']).'-'.sprintf('%02d',($request->transaction_date['month'])).'-'.sprintf('%02d',($request->transaction_date['day'])); 
							$receipt->transaction_date = date('Y-m-d',strtotime($t_date)); 
						}
						else {
							$receipt->transaction_date = '0000-00-00';
						}
						//$receipt->transaction_date = isset($request->transaction_date) ? ($request->transaction_date) : "0000-00-00";
						$receipt->transaction_bill = isset($request->transaction_bill) ? ($request->transaction_bill) : '';
						$receipt->transaction_book = isset($request->transaction_book) ? ($request->transaction_book) : '';
						$receipt->neft_adjust_id = isset($request->neft_adjust_id) ? ($request->neft_adjust_id) : 0;
						$receipt->receipt_mode = 'ERP';
						$receipt->receipt_time = isset($request->receipt_time) ? ($request->receipt_time) : '00:00:00';
						if(isset($request->accounts_date)) {
							$a_date = ($request->accounts_date['year']).'-'.sprintf('%02d',($request->accounts_date['month'])).'-'.sprintf('%02d',($request->accounts_date['day'])); 
							$receipt->accounts_date = date('Y-m-d',strtotime($a_date)); 
						}
						else {
							$receipt->accounts_date = date('Y-m-d');
						}
						//$receipt->accounts_date = isset($request->accounts_date) ? ($request->accounts_date) : "0000-00-00";
						$receipt->printed = isset($request->printed) ? ($request->printed) : 0;
						$receipt->auction_id = isset($request->Installments[$i]['auction_id']) ? $request->Installments[$i]['auction_id'] : '';
						$receipt->installment_no = isset($request->Installments[$i]['installment_no']) ? $request->Installments[$i]['installment_no'] : '';
						$receipt->pending_days = isset($request->Installments[$i]['pending_days']) ? $request->Installments[$i]['pending_days'] : '';
						$receipt->penalty = isset($request->Installments[$i]['penalty_inst_wise']) ? $request->Installments[$i]['penalty_inst_wise'] : '';
						$receipt->bonus = isset($request->Installments[$i]['bonus_inst_wise']) ? $request->Installments[$i]['bonus_inst_wise'] : '';
						$receipt->discount = isset($request->Installments[$i]['discount_inst_wise']) ? $request->Installments[$i]['discount_inst_wise'] : '';
						$receipt->received_amount = isset($request->Installments[$i]['received_amount']) ? $request->Installments[$i]['received_amount'] : '';
						$receipt->cancel_dividend_amount = isset($request->Installments[$i]['cancel_dividend_inst_wise']) ? $request->Installments[$i]['cancel_dividend_inst_wise'] : '';						
						$receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
						$receipt->created_by = isset($request->created_by) ? ($request->created_by) : 0;
						$receipt->status = isset($request->status) ? ($request->status) : 1;
						if($request->payment_type_id == 2){
							$receipt->status = isset($request->status) ? ($request->status) : 2;
						}
						if($receipt->amount >0)
						{
							if($receipt->penalty !=0 || $receipt->received_amount !=0) {
								if($receipt->save()) {
									if($receipt_no=='') {
										//sleep(10);
										$receipt_no = 'RCPT-'.sprintf('%05d', $receipt->id);
									}			
									$receipt->receipt_no = $receipt_no;
									$receipt->save();
									$group_name = isset($receipt->group_det->group_name) ? ($receipt->group_det->group_name) : '';
									$receiver = isset($receipt->customer_det->mobile_no) ? ($receipt->customer_det->mobile_no) : ''; 
									$ticket_no = isset($receipt->enrollment_det->ticket_no) ? ($receipt->enrollment_det->ticket_no) : [];
					$receipt_receipt_date = $receipt->receipt_date;
					$receipt->balance_amount = 0;
		$time = date("h:i:sa");
$msg = "Dear Customer, Your payment details
Date: $receipt_receipt_date Time: $time
Group name: $group_name
Ticket no: $ticket_no
Receipt No: $receipt->receipt_no
Receipt Amount: $receipt->amount
Balance Amount: $receipt->balance_amount
For complaints call 04142-267555
Thanks & Regards
TNV Chit Funds Pvt Ltd
Neyveli-607801";
	$msg1=$msg;
		
		$msg_split = str_split($msg,456);
		for($i=0;$i<sizeof($msg_split);$i++)
		{
		    
				$msg_temp =  urlencode( $msg_split[$i] );
            	$ch = curl_init("https://www.instaalerts.zone/SendSMS/sendmsg.php?uname=TNVCFPtr&pass=Abc@321&send=TNVCFP&dest=$receiver&msg=$msg_temp");
            
            	
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				  $result = curl_exec($ch);
				curl_close($ch);
		}	
									
									if($request->payment_type_id == 1){
										$add_exp = new AccountExpense();                
										$add_exp->tenant_id = isset($receipt->tenant_id) ? ($receipt->tenant_id) : 0;
										$add_exp->branch_id = isset($receipt->branch_id) ? ($receipt->branch_id) : 0;
										$add_exp->entry_date = isset($receipt->receipt_date) ? ($receipt->receipt_date) : 0;
										$add_exp->voucher_no = isset($receipt->receipt_no) ? ($receipt->receipt_no) : 0;
										$add_exp->debit_account_head = 0;
										$add_exp->debit_amount = 0;
										$add_exp->credit_account_head = 1;
										$add_exp->credit_amount = isset($receipt->amount) ? ($receipt->amount) : 0;
										$add_exp->status = 1;
										$add_exp->save();
									}
									else if(($request->payment_type_id == 2) || ($request->payment_type_id == 3)){
										
										$bank_id = isset($receipt->bank_name_id) ? ($receipt->bank_name_id) : 0;
										$bank = BankDetail::where('id','=',$receipt->bank_name_id)->get();
										foreach($bank as $banks) {
											$bank_account_no = isset($banks->account_number) ? ($banks->account_number) : 0;
											
										}
										$accnt_head = AccountHead::where('bank_account_number','=',$bank_account_no)->get();
										foreach($accnt_head as $accnt_heads) {
											$account_head_id = isset($accnt_heads->id) ? ($accnt_heads->id) : 0;
											
										}
										
										$add_exp = new AccountExpense();                
										$add_exp->tenant_id = isset($receipt->tenant_id) ? ($receipt->tenant_id) : 0;
										$add_exp->branch_id = isset($receipt->branch_id) ? ($receipt->branch_id) : 0;
										$add_exp->entry_date = isset($receipt->receipt_date) ? ($receipt->receipt_date) : 0;
										$add_exp->voucher_no = isset($receipt->receipt_no) ? ($receipt->receipt_no) : 0;
										$add_exp->debit_account_head = 0;
										$add_exp->debit_amount = 0;
										$add_exp->credit_account_head = $account_head_id;
										$add_exp->credit_amount = isset($receipt->amount) ? ($receipt->amount) : 0;
										$add_exp->status = 0;
										$add_exp->save();
									}
									else {
										$add_exp = new AccountExpense();                
										$add_exp->tenant_id = isset($receipt->tenant_id) ? ($receipt->tenant_id) : 0;
										$add_exp->branch_id = isset($receipt->branch_id) ? ($receipt->branch_id) : 0;
										$add_exp->entry_date = isset($receipt->receipt_date) ? ($receipt->receipt_date) : 0;
										$add_exp->voucher_no = isset($receipt->receipt_no) ? ($receipt->receipt_no) : 0;
										$add_exp->debit_account_head = 0;
										$add_exp->debit_amount = 0;
										$add_exp->credit_account_head = 1;
										$add_exp->credit_amount = isset($receipt->amount) ? ($receipt->amount) : 0;
										$add_exp->status = 1;
										$add_exp->save();
									}
									
									
									
			                	}
		                		else {
				                    $response['status']="Error";
				                    $response['msg']=\Lang::get('api.global_error');
				                    return response()->json($response,200);
			                	}
			            	}							
						}
						else
						{
							$response['status'] = 'Error';
					        $response['msg'] = \Lang::get('api.received_amt_not_found');
					        return response()->json($response,200);	
						}
		            }
		             // closed status update
	                $commission_auction = BiddingDetail::where('group_id',$request->group_id)->get();  
	                if(count($commission_auction)>0)
	                {                    
	                    $auction_count = $commission_auction->count();
	                    $slab_due = Group::where('id',$request->group_id)->get();
	                    $scheme = Scheme::where('id',$slab_due[0]->id)->get();
	                    $no_of_months = isset($scheme[0]->no_of_months) ? ($scheme[0]->no_of_months) : 0;                   
	                    if($no_of_months == $auction_count)
	                    { 
	                        $sum_amount = BiddingDetail::selectRaw('*,sum(current_installment_amount) as total_instal_amount')->where('group_id',$request->group_id)->get();    
	                        $instal_amout = $sum_amount[0]->total_instal_amount;

	                        $receipt = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus, sum(penalty) as total_penalty')->where('enrollment_id',$request->enrollment_id)->get();
	                        $total_receipt = isset($receipt[0]->total_receipt) ? ($receipt[0]->total_receipt) : 0;
	                        $total_bonus = isset($receipt[0]->total_bonus) ? ($receipt[0]->total_bonus) : 0;
	                        $total_paid = $total_receipt + $total_bonus;
	                       
	                        if($instal_amout == $total_paid)
	                        {                            
	                            $status_update = ChitDetail::where('id','=',$request->enrollment_id)->get();
	                            if(count($status_update)>0)
	                            {
	                                $status_update[0]->status = 2;
	                                $status_update[0]->save();
	                            }                        
	                        }
	                    }
	                }
            		$response['status'] = 'Success';
			        $response['msg'] = \Lang::get('api.success_receipt_added');
			        return response()->json($response,200);	
	            }				
				else {
					$response['status'] = 'Error';
					$response['msg'] = \Lang::get('api.no_data_found');
					return response()->json($response, 401);
				}
			}
			catch(\Exception $e) {
				$response['status'] = 'Error';
				$response['msg'] = \Lang::get('api.global_error');
				return response()->json($response, 401);
			}
		}
	}
	
    public function update_receipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required',
            'branch_id' => 'required',
            'customer_id' => 'required',
            'group_id' => 'required'
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
				if(isset($request->id)) {  
					$receipt = Receipt::find($request->id);
					$receipt->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
                    $receipt->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
                    $receipt->customer_id = isset($request->customer_id) ? ($request->customer_id) : 0;
                    $receipt->enrollment_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
                    $receipt->group_id = isset($request->group_id) ? ($request->group_id) : 0;
                    $receipt->ticket_no = isset($request->ticket_no) ? ($request->ticket_no) : '';
                    $receipt->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0;
                    $receipt->employee_branch_id = isset($request->employee_branch_id) ? ($request->employee_branch_id) : 0;
                    $receipt->auction_id = isset($request->auction_id) ? ($request->auction_id) : 0;
                    $receipt->receipt_no = isset($request->receipt_no) ? ($request->receipt_no) : '';
                    $receipt->pending_days = isset($request->pending_days) ? ($request->pending_days) : 0;
                    $receipt->adjust_id = isset($request->adjust_id) ? ($request->adjust_id) : 0;
                    $receipt->amount = isset($request->amount) ? ($request->amount) : 0;
                    $receipt->penalty = isset($request->penalty) ? ($request->penalty) : 0;
                    $receipt->bonus = isset($request->bonus) ? ($request->bonus) : 0;
                    $receipt->discount = isset($request->discount) ? ($request->discount) : 0;
                    $receipt->date = isset($request->date) ? ($request->date) : '0000-00-00';
                    $receipt->type_of_collection = isset($request->type_of_collection) ? ($request->type_of_collection) : '';
                    $receipt->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;
                    $receipt->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : '';
                    $receipt->cheque_date = isset($request->cheque_date) ? ($request->cheque_date) : '0000-00-00';
                    $receipt->cheque_clear_return_date = isset($request->cheque_clear_return_date) ? ($request->cheque_clear_return_date) : '0000-00-00';
                    $receipt->bank_name_id = isset($request->bank_name_id) ? ($request->bank_name_id) : 0;
                    $receipt->branch_name = isset($request->branch_name) ? ($request->branch_name) : '';
                    $receipt->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0;
                    $receipt->transaction_date = isset($request->transaction_date) ? ($request->transaction_date) : '0000-00-00';
                    $receipt->transaction_bill = isset($request->tr_bill) ? ($request->tr_bill) : '';
                    $receipt->transaction_book = isset($request->tr_book) ? ($request->tr_book) : '';
                    $receipt->neft_adjust_id = isset($request->neft_adjust_id) ? ($request->neft_adjust_id) : 0;
                    $receipt->receipt_mode = 'ERP';
                    $receipt->receipt_time = isset($request->receipt_time) ? ($request->receipt_time) : '00:00:00';
                    $receipt->accounts_date = isset($request->accounts_date) ? ($request->accounts_date) : '0000-00-00';
                    $receipt->printed = isset($request->printed) ? ($request->printed) : 0;
                    $receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
					$receipt->updated_by = isset($request->updated_by) ? ($request->updated_by) : 0;
					if($receipt->save()) {
						$response['status'] = "Success";
						$response['msg'] = \Lang::get('api.success_receipt_updated');
						$response['data'] = $receipt;
						return response()->json($response,200);
                	}
                	else {
	                    $response['status']="Error";
	                    $response['msg']=\Lang::get('api.global_error');
	                    return response()->json($response,401);
	                }
				}
                else {
                    $response['status'] = "Error";
                    $response['msg'] = \Lang::get('api.record_not_identified');
                    return response()->json($response, 200);
                }
            }
            catch(\Exception $e) { 
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }

    public function get_receipts(Request $request) {
        try {
            $cond = $request->conditions; 
            $receipts = Receipt::where($cond)->orderBy('id','desc')->get();
			$res_data = [];
			if(count($receipts)>0) {
				$res_data['tenant_id'] = $receipts[0]->tenant_id;
				$res_data['branch_id'] = $receipts[0]->branch_id;
				$res_data['other_branch'] = $receipts[0]->other_branch;
				$res_data['customer_id'] = $receipts[0]->customer_id;
				$res_data['enrollment_id'] = $receipts[0]->enrollment_id;
				$res_data['group_id'] = $receipts[0]->group_id;
				$res_data['ticket_no'] = $receipts[0]->ticket_no;
				$res_data['employee_id'] = $receipts[0]->employee_id;
				$res_data['receipt_date'] = ($receipts[0]->receipt_date!=0) ? date('d/m/Y',strtotime($receipts[0]->receipt_date)) : '';
				$res_data['payment_type_id'] = $receipts[0]->payment_type_id;
				$res_data['cheque_no'] = $receipts[0]->cheque_no;
				$res_data['cheque_date'] = ($receipts[0]->cheque_date!=0) ? date('d/m/Y',strtotime($receipts[0]->cheque_date)) : '';
				$res_data['bank_name_id'] = $receipts[0]->bank_name_id;
				$res_data['branch_name'] = $receipts[0]->branch_name;
				$res_data['transaction_no'] = $receipts[0]->transaction_no;
				$res_data['transaction_date'] = ($receipts[0]->transaction_date!=0) ? date('d/m/Y',strtotime($receipts[0]->transaction_date)) : '';
				$res_data['debit_to'] = $receipts[0]->debit_to;
				$res_data['amount'] = $receipts[0]->amount;
				$res_data['remarks'] = $receipts[0]->remarks;
				$pending_details = [];
				foreach($receipts as $receipt) {
					$receipt_det = [];
					$receipt_det['auction_id'] = $receipt->auction_id;
					$receipt_det['installment_no'] = $receipt->installment_no;
					$receipt_det['inst_amt_wise_pending'] = $receipt->inst_amt_wise_pending;
					$receipt_det['received_amount'] = $receipt->received_amount;
					$receipt_det['penalty_inst_wise'] = $receipt->penalty_inst_wise;
					$receipt_det['bonus_inst_wise'] = $receipt->bonus_inst_wise;
					$receipt_det['discount_inst_wise'] = $receipt->discount_inst_wise;
					$receipt_det['discount_penalty_wise'] = $receipt->discount_penalty_wise;
					$receipt_det['pending_days'] = $receipt->pending_days;
					$receipt_det['id'] = $receipt->id;
					array_push($pending_details,$receipt_det);
				}
				$res_data['pending_details'] = $pending_details;
			}
            $response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $res_data;
            return response()->json($response,200);
        }
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

    public function delete_receipt(Request $request) {
		try {
            if(isset($request->receipt_no)) {
				
                $receipt_list = Receipt::where('receipt_no',$request->receipt_no)->get(); 
                foreach($receipt_list as $receipt) {
	                $receipt->deleted_by = $request->deleted_by;
	                $receipt->deletion_remark =  isset($request->deletion_remark) ? ($request->deletion_remark) : 0;
	                if($receipt->save()) {
	                	$receipt->delete();
	                }
	                else {
	                	$response['status'] = 'Error';
			            $response['msg'] = \Lang::get('api.global_error');
			            return response()->json($response, 401);
	                }
                }
                $response['status'] = 'Success';
                $response['msg'] = \Lang::get('api.success_receipt_deleted');
                return response()->json($response,200);
            }
            else {
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.record_not_identified');
                return response()->json($response, 200);
            }
        }
        catch(\Exception $e) { 
            $response['status'] = "Error";
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        } 
    }

    public function cheque_return(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'cheque_clear_return_date' => 'required',
            'cheque_return_amount' => 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
            	$receipt = Receipt::where('id',$request->id)->get();
                $rcpt_no = Receipt::where('receipt_no',$receipt[0]->receipt_no)->get();
                foreach($rcpt_no as $rcpt)
                {                  
                	$receipt_insert = Receipt::find($rcpt->id);                 
	                if(isset($request->cheque_clear_return_date)) {
	                    $ccr_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
	                    $receipt_insert->cheque_clear_return_date = date('Y-m-d',strtotime($ccr_date));
	                }
	                else {
	                    $receipt_insert->cheque_clear_return_date = '0000-00-00';
	                } 
	                $receipt_insert->remarks = isset($request->remarks) ? ($request->remarks) : '';
	                $receipt_insert->cheque_return_amount = isset($request->cheque_return_amount) ? ($request->cheque_return_amount) : 0;
	                $receipt_insert->status = isset($request->status) ? ($request->status) : 3;
	                $receipt_insert->save();	                   
	            }
	            $response['status'] = 'Success';
                $response['msg'] = \Lang::get('api.success_cheque_returned');
                $response['data'] = $rcpt_no;
                return response()->json($response,200);
            }
            catch(\Exception $e) {
                $response['status'] = 'Error';
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }

    public function cheque_cleared(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'cheque_clear_return_date' => 'required',
            'cheque_debit_to' => 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
                $receipt = Receipt::where('id',$request->id)->get();
                $rcpt_no = Receipt::where('receipt_no',$receipt[0]->receipt_no)->get();
				
                foreach($rcpt_no as $rcpt)
                {       
					$receipt_no_account_exp = $rcpt->receipt_no; 
					
                	$receipt_insert = Receipt::find($rcpt->id); 
	                if(isset($request->cheque_clear_return_date)) {
	                    $ccr_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
	                    $receipt_insert->cheque_clear_return_date = date('Y-m-d',strtotime($ccr_date));
	                }
	                else {
	                    $receipt_insert->cheque_clear_return_date = '0000-00-00';
	                }
	                //$receipt_insert->cheque_clear_return_date = isset($request->cheque_clear_return_date) ? ($request->cheque_clear_return_date) : "0000-00-00";
	                $receipt_insert->remarks = isset($request->remarks) ? ($request->remarks) : '';
	                $receipt_insert->cheque_debit_to = isset($request->cheque_debit_to) ? ($request->cheque_debit_to) : 0; 
	                $receipt_insert->status = isset($request->status) ? ($request->status) : 1;
	                $receipt_insert->save();
					
					$receipt_account_exp = AccountExpense::where('voucher_no','=',$rcpt->receipt_no)->get();
					foreach($receipt_account_exp as $receipt_account)
					{
						if(isset($receipt_account->id) && !empty($receipt_account->id))
							{
								$exp_type2=AccountExpense::find($receipt_account->id);
								$exp_type2->status =  1;
								$exp_type2->save();
							}
					}
                }
                $response['status'] = 'Success';
                $response['msg'] = \Lang::get('api.success_cheque_cleared');
                //$response['data'] = $rcpt_no;
                return response()->json($response,200);
            }
            catch(\Exception $e) {
                $response['status'] = 'Error';
                $response['msg'] = \Lang::get('api.global_error');
                $response['msg'] = $e;
                return response()->json($response, 401);
            }
        }
    }
	
	public function get_enrol_details(Request $request) {
        try {
			
			$cr_date = date("Y-m-d");
            //enrollment id
            $recdate = '';
            $s_date = ($request->recdate['year']).'/'.sprintf('%02d',($request->recdate['month'])).'/'.sprintf('%02d',($request->recdate['day']));
        	$rec_date = date('Y-m-d',strtotime($s_date));
        	//$rec_date = $request->recdate;
            $enrl_id = $request->enrolid;
            if($rec_date == $cr_date) 
            {			
	            $chit_details = ChitDetail::where('id','=',$enrl_id)->orderBy('id','desc')->get();				
				$response_data = [];			
				foreach($chit_details as $cd) 
				{
					$enrollment_id = $cd->id;
			        $group_id = $cd->group_id;
			        $cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
			        $customer_id = $cd->customer_id;
					$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
					$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
					//total_installment_amount
					$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');
					$cd->overall_installment_amount = $total_installment_amount;
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = $receipt[0]->total_paid;
					$bonus_amount = $receipt[0]->bonus_amount;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;
					//pending_amount 
					$pending_amount = $total_installment_amount - $paid_amount;
					$cd->overall_pending_amount = $pending_amount;
					$cd->chit_value = $chit_value;
					//last auction details
					$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
					$current_inst_amt = BiddingDetail::where('Id','=',$max_bid_id)->pluck('current_installment_amount');
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   //auction wise details 
					$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
					$i=0;
					$install_det = [];					
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) 
					{
						$cust_det = [];		
						$penalty = 0;					
						$bonus = 0;					
						$inst_amt = $auc_wise_inst_amt->current_installment_amount;
						$auction_id = $auc_wise_inst_amt->id;
						$auc_id  = $auc_wise_inst_amt->id;
						$installment_no  = $auc_wise_inst_amt->installment_no;
						$auc_date_inst  = $auc_wise_inst_amt->auction_date;
	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();	
						 $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						 $last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) { 
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
						  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $inst_amt_no !=0) {
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						}
						// Bonus days and percentage by heirrachy over all 
						if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
						{
						  $bonus_days = $cd->customer_det->bonus_days;
						  $bonus_percentage = $cd->customer_det->customer_bonus;
						}    
						elseif($cd->group_det->group_bonus_days!=0 && $cd->group_det->group_base_bonus!=0)
						{
						  $bonus_days = $cd->group_det->group_bonus_days;
						  $bonus_percentage = $cd->group_det->group_base_bonus;
						}
						elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
						{
						  $bonus_days = $cd->branch_det->bonus_days;
						  $bonus_percentage = $cd->branch_det->bonus;              
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 			
						
						$bid = BiddingDetail::Where('group_id',$group_id)->max('id');
						$check_auction = BiddingDetail::where('id',$bid)->get();
						$max_auction_date = $check_auction[0]->auction_date;
						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$cr_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 
							//echo $pending_days ."<=". $bonus_days_include_holiday;
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
						  $cust_det['bonus'] = 0;
						}
						
						// Penalty Calculation installment wise
		                if($cd->customer_det->penalty_days!=0 )
		                {
		                  $inst_penalty_days = $cd->customer_det->penalty_days;
		                  $inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
		                }
		                elseif($cd->group_det->group_penalty_days!=0 )
		                {
		                  $inst_penalty_days = $cd->group_det->group_penalty_days;
		                  $inst_penalty_percentage = $cd->group_det->group_base_penalty;
		                }
		                elseif($cd->branch_det->penalty_days!=0)
		                {
		                  $inst_penalty_days = $cd->branch_det->penalty_days;
		                  $inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
		                }
		                else
		                {
		                  $inst_penalty_days = "0";
		                  $inst_penalty_percentage = "0";
		                }

		                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
		                $pend_days_int = 0;    
		                $cust_det['penalty_amounts'] = 0;   
						
		                if($pending_days > $inst_penalty_days)
		                {     		
		                  	$branch=Branch::where('id',$cd->branch_id)->get();
		                  	if($cd->prized_status==0)
		                  	{
		                    	$non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
		                    	$pend_days_int = $pending_days * ($non_prize/ 100);
		                    	$current_month_days = date('t'); 
		                    	$total_days_int = $pend_days_int / $current_month_days;
		                    	$penalty = $total_days_int * $ins_amt_wise_rec;
		                    	$cust_det['penalty_amounts'] = strval(round($penalty));
		                  	} 
		                  	else
		                  	{               
		                    	$prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
		                    	$pend_days_int = $pending_days * ($prize / 100);
		                    	$current_month_days = date('t'); 
		                    	$total_days_int = $pend_days_int / $current_month_days;
		                    	$penalty = $total_days_int * $ins_amt_wise_rec;
		                    	$cust_det['penalty_amounts'] = strval(round($penalty));
		                  	}                        		  
	                	}    
					
						$cust_det['installment_no'] = $installment_no;
						$cust_det['inst_amt_wise'] = $inst_amt;
						$cust_det['amount_paid'] = $amount_paid;
						$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
						$cust_det['pending_days'] = $pending_days;
						$cust_det['bonus_inst_wise'] = 0;
						$cust_det['penalty_inst_wise'] = 0;
						$cust_det['discount_inst_wise'] = 0;
						$cust_det['discount_penalty_wise'] = 0;
						$cust_det['auction_id'] = $auction_id;
						$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
						$cust_det['cancel_dividend_amount'] =0;
						if($ins_amt_wise_rec>0) {
							array_push($install_det,$cust_det);
						}	
						$total_penalty_amounts += $penalty;
						$total_bonus_amounts += $bonus;
					};
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$cd->pending_details = $install_det;
					$cd->scheme_info = [];
					if(isset($cd->group_det)) {
						$cd->scheme_info = isset($cd->group_det->scheme_det) ? ($cd->group_det->scheme_det) : [];
					}
					if($cd) {
						array_push($response_data,$cd);
					}
				}
			} else if($rec_date != $cr_date)  
			{  
				$chit_details = ChitDetail::where('id','=',$enrl_id)->orderBy('id','desc')->get();			
				$response_data = [];			
				foreach($chit_details as $cd) {
					$enrollment_id = $cd->id;
			        $group_id = $cd->group_id;
			        $branch_id = $cd->branch_id;
			        $cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
			        $customer_id = $cd->customer_id;
					$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
					$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value'); 
					//total_installment_amount
					$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');
					$cd->overall_installment_amount = $total_installment_amount;
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = $receipt[0]->total_paid;
					$bonus_amount = $receipt[0]->bonus_amount;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;
					//pending_amount 
					$pending_amount = $total_installment_amount - $paid_amount;
					$cd->overall_pending_amount = $pending_amount;
					$cd->chit_value = $chit_value;
					//last auction details
					$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
					$current_inst_amt = BiddingDetail::where('Id','=',$max_bid_id)->pluck('current_installment_amount');
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   //auction wise details 
					$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
					$i=0;
					$install_det = [];					
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
						$cust_det = [];		
						$penalty = 0;					
						$bonus = 0;					
						$inst_amt = $auc_wise_inst_amt->current_installment_amount;
						$auction_id = $auc_wise_inst_amt->id;
						$auc_id  = $auc_wise_inst_amt->id;
						$installment_no  = $auc_wise_inst_amt->installment_no;
						$auc_date_inst  = $auc_wise_inst_amt->auction_date;
	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();	
						 $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						 $last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) { 
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
						  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $inst_amt_no !=0) {
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');	
							$i++;
						}  			
						/*if($auc_date_inst <= $rec_date)	
						{
							$cust_det['before_auction_date'] = "Auction ";
						}	
						else{
							$cust_det['before_auction_date'] = "cc";
						}*/	
						// Branch - old bonus, penalty calculation
						$up_branch = UpdateBranch::where('branch_id',$branch_id)->where('edit_date','<=',$rec_date)->max('id');			
						$update_branches = UpdateBranch::where('id',$up_branch)->get();
						if(count($update_branches)>0)
						{
							$branch_bonus_days = isset($update_branches[0]->bonus_days) ? ($update_branches[0]->bonus_days) : 0;
							$branch_bonus_percentage = isset($update_branches[0]->bonus) ? ($update_branches[0]->bonus) : 0;
							$branch_penalty_days = isset($update_branches[0]->penalty_days) ? ($update_branches[0]->penalty_days) : 0;
		                	$branch_penalty_percentage = isset($update_branches[0]->branch_wise_penalty) ? ($update_branches[0]->branch_wise_penalty) : 0;
						}
						else
						{
							$branch_bonus_days = 0;
							$branch_bonus_percentage = 0;
							$branch_penalty_days =0;
							$branch_penalty_percentage =0;
						}

						// Group - old bonus, penalty calculation
						$up_group = UpdateGroup::where('group_id',$group_id)->where('edit_date','<=',$rec_date)->max('id');			
						$update_groups = UpdateGroup::where('id',$up_group)->get();
						if(count($update_groups)>0)
						{
							$group_bonus_days = isset($update_groups[0]->group_bonus_days) ? ($update_groups[0]->group_bonus_days) : 0; 
							$group_bonus_percentage = isset($update_groups[0]->group_base_bonus) ? ($update_groups[0]->group_base_bonus) : 0;
							$group_penalty_days = isset($update_groups[0]->group_penalty_days) ? ($update_groups[0]->group_penalty_days) : 0;
							$group_penalty_percentage = isset($update_groups[0]->group_base_penalty) ? ($update_groups[0]->group_base_penalty) : 0;
						}
						else
						{
							$group_bonus_days =0;
							$group_bonus_percentage =0;
							$group_penalty_days =0;
							$group_penalty_percentage =0;
						} 
						// Customer - old bonus, penalty calculation
						$up_customer = UpdateCustomer::where('update_customer_id',$customer_id)->where('edit_date','<=',$rec_date)->max('id');
						$update_customers = UpdateCustomer::where('id',$up_customer)->get();
						if(count($update_customers)>0)
						{
							$cust_bonus_days = isset($update_customers[0]->bonus_days) ? ($update_customers[0]->bonus_days) : 0;
							$cust_bonus_percentage = isset($update_customers[0]->customer_bonus) ? ($update_customers[0]->customer_bonus) : 0;
							$cust_penalty_days = isset($update_customers[0]->penalty_days) ? ($update_customers[0]->penalty_days) : 0;
							$cust_penalty_percentage = isset($update_customers[0]->customer_penalty_interest) ? ($update_customers[0]->customer_penalty_interest) : 0;
						}
						else
						{	
							$cust_bonus_days =0;
							$cust_bonus_percentage = 0;
							$cust_penalty_days = 0;
							$cust_penalty_percentage =0;
						}
						
						// Bonus days and percentage by heirrachy over all 
						if($cust_bonus_days!=0 && $cust_bonus_percentage!=0)
						{
						  $bonus_days = $cust_bonus_days;
						  $bonus_percentage = $cust_bonus_percentage;
						}    
						elseif($group_bonus_days!=0 && $group_bonus_percentage!=0)
						{
						  $bonus_days = $group_bonus_days;
						  $bonus_percentage = $group_bonus_percentage;
						}
						elseif($branch_bonus_days!=0 && $branch_bonus_percentage!=0)
						{
						  $bonus_days = $branch_bonus_days;
						  $bonus_percentage = $branch_bonus_percentage;
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 		

						$bid = BiddingDetail::Where('group_id',$group_id)->max('id');
						$check_auction = BiddingDetail::where('id',$bid)->get();
						$max_auction_date = $check_auction[0]->auction_date;
						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$rec_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
						  $cust_det['bonus'] = 0;
						}						

						// Penalty Calculation installment wise
		                if($cust_penalty_days!=0 )
		                {
		                  $inst_penalty_days = $cust_penalty_days;
		                  $inst_penalty_percentage = $cust_penalty_percentage;
		                }
		                elseif($group_penalty_days!=0 )
		                {
		                  $inst_penalty_days = $group_penalty_days;
		                  $inst_penalty_percentage = $group_penalty_percentage;
		                }
		                elseif($branch_penalty_days!=0)
		                {
		                  $inst_penalty_days = $branch_penalty_days;
		                  $inst_penalty_percentage = $branch_penalty_percentage;
		                }
		                else
		                {
		                  $inst_penalty_days = "0";
		                  $inst_penalty_percentage = "0";
		                }
		                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
		                $pend_days_int = 0;    
		                $cust_det['penalty_amounts'] = 0;   
						
		                if($pending_days > $inst_penalty_days)
		                {     	
		                  if($cd->prized_status==0)
		                  {
		                    $non_prize = isset($update_branches[0]->non_prize_subscriber_penalty) ? ($update_branches[0]->non_prize_subscriber_penalty) : 0;
		                    $pend_days_int = $pending_days * ($non_prize/ 100);
		                    $current_month_days = date('t'); 
		                    $total_days_int = $pend_days_int / $current_month_days;
		                    $penalty = $total_days_int * $ins_amt_wise_rec;
		                    $cust_det['penalty_amounts'] = strval(round($penalty));
		                  } 
		                  else
		                  {               
		                    $prize = isset($update_branches[0]->prize_subscriber_penalty) ? ($update_branches[0]->prize_subscriber_penalty) : 0;
		                    $pend_days_int = $pending_days * ($prize / 100);
		                    $current_month_days = date('t'); 
		                    $total_days_int = $pend_days_int / $current_month_days;
		                    $penalty = $total_days_int * $ins_amt_wise_rec;
		                    $cust_det['penalty_amounts'] = strval(round($penalty));
		                  }                        		  
		                }    
						
						$cust_det['installment_no'] = $installment_no;
						$cust_det['inst_amt_wise'] = $inst_amt;
						$cust_det['amount_paid'] = $amount_paid;
						$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
						$cust_det['pending_days'] = $pending_days;
						$cust_det['bonus_inst_wise'] = 0;
						$cust_det['penalty_inst_wise'] = 0;
						$cust_det['discount_inst_wise'] = 0;
						$cust_det['discount_penalty_wise'] = 0;
						$cust_det['auction_id'] = $auction_id;
						$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
						$cust_det['cancel_dividend_amount'] =0;
						if($ins_amt_wise_rec>0) {
							array_push($install_det,$cust_det);
						}	
						$total_penalty_amounts += $penalty;
						$total_bonus_amounts += $bonus;
					};
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$cd->pending_details = $install_det;
					$cd->scheme_info = [];
					if(isset($cd->group_det)) {
						$cd->scheme_info = isset($cd->group_det->scheme_det) ? ($cd->group_det->scheme_det) : [];
					}
					if($cd) {
						array_push($response_data,$cd);
					}
				}			
			}
			$response['status'] = 'Success';
			$response['msg'] = "";
			$response['data'] = $response_data;
			return response()->json($response,200);	
		}
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }
	
	public function list_receipts(Request $request) {
        try {  
            $cond = [];
            $start_date = null;
            $end_date = null;
            if(isset($request->tenant_id)){
				$cond['tenant_id'] = $request->tenant_id;
			}
			if(isset($request->branch_id)){
				$cond['branch_id'] = $request->branch_id;
			}
			if(isset($request->group_id)){
				$cond['group_id'] = $request->group_id; 
			}
			if(isset($request->customer_id)){
				$cond['customer_id'] = $request->customer_id; 
			}
			if(isset($request->employee_id)){
				$cond['employee_id'] = $request->employee_id; 
			}
			if(isset($request->payment_type_id)){
				$cond['payment_type_id'] = $request->payment_type_id; 
			} 
			if(isset($request->start_date)) { 
        		$s_date = ($request->start_date['year']).'-'.sprintf('%02d',($request->start_date['month'])).'-'.sprintf('%02d',($request->start_date['day']));
        		$start_date = date('Y-m-d',strtotime($s_date)); 
        	}				
        	if(isset($request->end_date)) {
        		$s_date = ($request->end_date['year']).'-'.sprintf('%02d',($request->end_date['month'])).'-'.sprintf('%02d',($request->end_date['day'])); 
        		$end_date = date('Y-m-d',strtotime($s_date)); 
        	}             
			$response["start_date"] = $start_date;
			$response["end_date"] = $end_date;
			$receipts = DB::select('call receipt_report(?,?,?,?,?,?,?,?)',array($request->tenant_id,$request->branch_id,$request->group_id,$request->customer_id,$request->employee_id,$request->payment_type_id,$start_date,$end_date));

            $i=1;  
            $grand_tot_receipt_amt =0;
            $tot_receipt_amt =0;
            $grand_tot_received_amt =0;
            $tot_received_amt =0;
            $grand_tot_penalty_amount =0;
            $tot_penalty_amount =0;
            $grand_tot_bonus_amount = 0;
            $tot_bonus_amount =0;
            foreach($receipts as $receipt) {
                $receipt->sno = $i;
				$receipt->tenant_info = isset($receipt->tenant_det) ? ($receipt->tenant_det) : [];
				$receipt->branch_name = isset($receipt->branchName) ? ($receipt->branchName) : '-';
				$receipt->group_name = isset($receipt->group_name) ? ($receipt->group_name) : '-';
				$receipt->employee_name = isset($receipt->first_name) ? ($receipt->first_name) : '-';
				$receipt->customer_name = isset($receipt->name) ? ($receipt->name) : '';
				$receipt->enrollment_branch_name = isset($receipt->enroll_branch) ? ($receipt->enroll_branch) : [];	
				$receipt->payment_type_name = '-';
				if(isset($receipt->payment_type_id)) {
					$payment_type_det = PaymentType::find($receipt->payment_type_id);	
					$receipt->payment_type_name = isset($payment_type_det) ? ($payment_type_det->payment_name) : '-';
				}
				$receipt->bank_name = '-';
				$receipt->bank_branch_name = '-';
				if(isset($receipt->bank_name_id)) {
					$bank_det = BankDetail::find($receipt->bank_name_id);	
					$receipt->bank_name = isset($bank_det) ? ($bank_det->bank_name) : '-';
					$receipt->bank_branch_name = isset($bank_det) ? ($bank_det->bank_branch_name) : '-';
				}
				$total_paid = isset($receipt->total_paid) ? ($receipt->total_paid) : 0;
				$receipt->total_paid = $total_paid;
				$bonus_amount = $receipt->bonus_amount;
				$receipt->bonus_amount = $bonus_amount;
				$penalty_amount = $receipt->penalty_amount;
				$receipt->penalty_amount = $penalty_amount;
				$total_received_amount = $total_paid + $penalty_amount;
				$receipt->total_received_amount = $total_received_amount;
				$receipt->other_branch_info = [];
				$receipt->other_branch_name = '-';
				if(($receipt->other_branch!=0) && ($receipt->enrollment_id)) {
					$other_branch_det = ChitDetail::find($receipt->enrollment_id);
					$receipt->other_branch_info = isset($other_branch_det->branch_det) ? ($other_branch_det->branch_det) : [];
					$receipt->other_branch_name = isset($other_branch_det->branch_det) ? ($other_branch_det->branch_det->branch_name) : '-';
				}
				$receipt->receipt_date = ($receipt->receipt_date!=0) ? date('d/m/Y',strtotime($receipt->receipt_date)) : '0000-00-00';
				$receipt->accounts_date = ($receipt->accounts_date!=0) ? date('d/m/Y',strtotime($receipt->accounts_date)) : '0000-00-00';
				$receipt->cheque_date = ($receipt->cheque_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_date)) : '0000-00-00';
				$receipt->cheque_clear_return_date = ($receipt->cheque_clear_return_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_clear_return_date)) : '0000-00-00';
				$receipt->cheque_bank_name = '-';
				if(isset($receipt->cheque_debit_to)) {
					$cheque_bank_det = BankDetail::find($receipt->cheque_debit_to);	
					$receipt->cheque_bank_name = isset($cheque_bank_det) ? ($cheque_bank_det->bank_name) : '-';
				}
				if($receipt->status == 1 )
				{
					$receipt->status_name = "Active";
				}	
				else if($receipt->status == 2 )
				{
					$receipt->status_name = "Pending";
				}		
				else if($receipt->status == 3 )
				{
					$receipt->status_name = "Return";
				}	
				else if($receipt->status == 0 )
				{
					$receipt->status_name = "In-Active";
				}else
				{
					$receipt->status_name = "-";
				}
                $i++;
				$tot_receipt_amt += $total_paid;
				$tot_received_amt += $total_received_amount;
				$tot_penalty_amount += $penalty_amount;
				$tot_bonus_amount += $bonus_amount;
				
            }
            $grand_tot_receipt_amt += $tot_receipt_amt;
            $grand_tot_received_amt += $tot_received_amt;
            $grand_tot_penalty_amount += $tot_penalty_amount;
            $grand_tot_bonus_amount += $tot_bonus_amount;
            $response['grand_tot_receipt_amt'] = $grand_tot_receipt_amt;
            $response['grand_tot_received_amt'] = $grand_tot_received_amt;
            $response['grand_tot_penalty_amount'] = $grand_tot_penalty_amount;
            $response['grand_tot_bonus_amount'] = $grand_tot_bonus_amount;
            $response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $receipts;
            return response()->json($response,200);
        }
        catch(\Exception $e) {
		$response['status'] = 'Error';
		$response['msg'] = \Lang::get('api.global_error');
        return response()->json($response, 401);
        }
    }
	
	/*public function get_dashboard(Request $request) 
	{
        try 
        { 
		    $first_day_this_month = date('Y-m-01'); 
			$last_day_this_month  = date('Y-m-t');
			$dashboards = Branch::where('id','=',$request->branch_id)->get();
			foreach($dashboards as $dashboard) {
				//group count 
				$group_count = Group::where('branch_id','=',$request->branch_id)->count();
				$dashboard->group_count = $group_count; 
				$ato_monthly = 0;
			 	if(isset($request->tenant_id)) { 
					$dashboard = [];	 
					//group count
		            if(isset($request->branch_id)) {  
						$group_count = Group::where(array('branch_id'=>$request->branch_id,'group_status'=>0))->count();
						$dashboard['group_count'] = $group_count;
			 		}
			 		else {
						$group_count = Group::where(array('tenant_id'=>$request->tenant_id,'group_status'=>0))->count();
						$dashboard['group_count'] = $group_count;
					}
					//vacant group
					if(isset($request->branch_id)) {  
						$group_vacant = Group::where(array('branch_id'=>$request->branch_id,'is_filled'=>1))->count();
						$dashboard['group_vacant'] = $group_vacant;
					} 
					else {
						$group_vacant = Group::where(array('tenant_id'=>$request->tenant_id,'is_filled'=>1))->count();
						$dashboard['group_vacant'] = $group_vacant;	
					} 
					//new group
					if(isset($request->branch_id)) {  
						$new_group_count = Group::where('branch_id','=',$request->branch_id)->whereBetween('starting_date',[$first_day_this_month,$last_day_this_month])->count();
						$dashboard['new_group_count'] = $new_group_count;
					} 
					else {
						$new_group_count = Group::where('tenant_id','=',$request->tenant_id)->whereBetween('starting_date',[$first_day_this_month,$last_day_this_month])->count();
						$dashboard['new_group_count'] = $new_group_count;	
					}
					//no of customers
					if(isset($request->branch_id)) {   
						$customer_count = Customer::where('branch_id','=',$request->branch_id)->count();
						$dashboard['customer_count'] = $customer_count;
					} 
					else {
						$customer_count = Customer::where('tenant_id','=',$request->tenant_id)->count();
						$dashboard['customer_count'] = $customer_count;
					}
					//bid payment outstanding
					if(isset($request->branch_id)) {  
						$bidding_oustanding = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('pending_release');
						$dashboard['bidding_oustanding'] = $bidding_oustanding;
					} 
					else {
						$bidding_oustanding = BiddingDetail::where('tenant_id','=',$request->tenant_id)->sum('pending_release');
						$dashboard['bidding_oustanding'] = $bidding_oustanding;	
					} 
					//this month outstanding
					$penalty_amount = 0;
					$total_paid = 0; 
					if(isset($request->branch_id)) {  
					    $this_month_collections = Receipt::selectRaw('sum(received_amount) as total_paid, sum(penalty) as penalty_amount')->where('branch_id','=',$request->branch_id)->whereBetween('receipt_date',[$first_day_this_month,$last_day_this_month])->get();
						$total_paid = $this_month_collections[0]->total_paid;
						$penalty_amount = $this_month_collections[0]->penalty_amount;
						$dashboard['over_all_collection'] = $total_paid + $penalty_amount;
					} 
					else {
						$this_month_collections = Receipt::selectRaw('sum(received_amount) as total_paid, sum(penalty) as penalty_amount')->where('tenant_id','=',$request->tenant_id)->whereBetween('receipt_date',[$first_day_this_month,$last_day_this_month])->get();
						$total_paid = $this_month_collections[0]->total_paid;
						$penalty_amount = $this_month_collections[0]->penalty_amount;
						$dashboard['over_all_collection'] = $total_paid + $penalty_amount;	
					}  
					//auction turn over_all_collection
					if(isset($request->branch_id)) {   
						$group_ato = Group::where(array('branch_id'=>$request->branch_id,'group_status'=>0,'is_first_auction_complete'=>0))->pluck('scheme_id');
						foreach($group_ato as $ato) {  
							$scheme_id = $ato;  
							$chitvalue = Scheme::where('id','=',$scheme_id)->get();  
							if(count($chitvalue)>0) { 
								$ato_monthly += $chitvalue[0]->chit_value;
							}
						} 
					}
					else {
						$group_ato = Group::where(array('tenant_id'=>$request->tenant_id,'group_status'=>0,'is_first_auction_complete'=>0))->pluck('scheme_id');
						foreach($group_ato as $ato) {
							$scheme_id = $ato;
							$chitvalue = Scheme::where('id','=',$scheme_id)->get();
							if(count($chitvalue)>0) { 
								$ato_monthly += $chitvalue[0]->chit_value;
							}
						}
					} 
					//bid payment outstanding
					if(isset($request->branch_id)) {  
						$bidding_oustanding_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('pending_release');
						$dashboard['bidding_oustanding_monthly'] = $bidding_oustanding_monthly;
					} 
					else {
						$bidding_oustanding_monthly = BiddingDetail::where('tenant_id','=',$request->tenant_id)->sum('pending_release');
						$dashboard['bidding_oustanding_monthly'] = $bidding_oustanding_monthly;	 
					} 
				   	//Bid Amount
				   	if(isset($request->branch_id)) {   
					    $bidding_amount_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('bidding_amount');
						$dashboard['bidding_amount_monthly'] = $bidding_amount_monthly;
				   	} 
				   	else {
						$bidding_amount_monthly = BiddingDetail::where('tenant_id','=',$request->tenant_id)->sum('bidding_amount');
						$dashboard['bidding_amount_monthly'] = $bidding_amount_monthly;  
				   	} 
				    //Bid Payable
					if(isset($request->branch_id)) {  
						$bidding_payable_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('customer_released');
						$dashboard['bidding_payable_monthly'] = $bidding_payable_monthly;
					} 
					else {
						$bidding_payable_monthly = BiddingDetail::where('tenant_id','=',$request->tenant_id)->sum('customer_released');
						$dashboard['bidding_payable_monthly'] = $bidding_payable_monthly;	
					}  
				} 
				else {
					$dashboard = [];
					//group count 
					$group_count = Group::where(array('group_status'=>0))->count();
					$dashboard['group_count'] = $group_count;
					//vacant group
					$group_vacant = Group::where(array('is_filled'=>1))->count();
					$dashboard['group_vacant'] = $group_vacant;
					//new group
					$new_group_count = Group::whereBetween('starting_date',[$first_day_this_month,$last_day_this_month])->count();
					$dashboard['new_group_count'] = $new_group_count;
					//no of customers
					$customer_count = Customer::orderBy('id','desc')->count();
					$dashboard['customer_count'] = $customer_count;
					//bid payment outstanding
					$bidding_oustanding = BiddingDetail::sum('pending_release');
					$dashboard['bidding_oustanding'] = $bidding_oustanding;
					//this month outstanding
					$penalty_amount = 0;
					$total_paid = 0;
				    $this_month_collections = Receipt::selectRaw('sum(received_amount) as total_paid, sum(penalty) as penalty_amount')->whereBetween('receipt_date',[$first_day_this_month,$last_day_this_month])->get();
					$total_paid = $this_month_collections[0]->total_paid;
					$penalty_amount = $this_month_collections[0]->penalty_amount;
					$dashboard['over_all_collection'] = $total_paid + $penalty_amount;
					//auction turn over_all_collection
					$group_ato = Group::where(array('group_status'=>0,'is_first_auction_complete'=>0))->pluck('scheme_id');
					foreach($group_ato as $ato) {
						$scheme_id = $ato;
						$chitvalue = Scheme::where('id','=',$scheme_id)->get();
						if(count($chitvalue)>0) { 
							$ato_monthly += $chitvalue[0]->chit_value;
						}
					};
					//bid payment outstanding
					$bidding_oustanding_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('pending_release');
					$dashboard['bidding_oustanding_monthly'] = $bidding_oustanding_monthly;
				   	//Bid Amount
				    $bidding_amount_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('bidding_amount');
					$dashboard['bidding_amount_monthly'] = $bidding_amount_monthly;
				    //Bid Payable
					$bidding_payable_monthly = BiddingDetail::where('branch_id','=',$request->branch_id)->sum('customer_released');
					$dashboard['bidding_payable_monthly'] = $bidding_payable_monthly;
			   
			 	}
		 	}
            $response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $dashboards;
            return response()->json($response,200);
		}
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        } 
    }*/

    public function get_dashboard(Request $request) 
	{
        try 
        { 
		    $first_day_this_month = date('Y-m-01'); 
			$last_day_this_month  = date('Y-m-t');
			$running_groups_count = 0;
			$vacant_groups_count = 0;
			$new_groups_count = 0;
			$customers_count = 0;
			$total_bidding_oustanding = 0;
			$current_month_collection = 0;
			$auction_turn_over = 0;
			$monthly_bidding_outstanding = 0;
			$monthly_bidding_amount = 0;
			$monthly_bidding_payable = 0;

			$dashboard = [];

			if(isset($request->branch_id)) { 
				$branch_list = Branch::where('id','=',$request->branch_id)->get();
				$customer_list = Customer::where('branch_id','=',$request->branch_id)->get();
				$customers_count = count($customer_list);
			}
			else if(isset($request->tenant_id)) { 
				$branch_list = Branch::where('tenant_id','=',$request->tenant_id)->get();
				$customer_list = Customer::where('tenant_id','=',$request->tenant_id)->get();
				$customers_count = count($customer_list);
			}
			else { 
				$branch_list = Branch::all();
				$customer_list = Customer::all();
				$customers_count = count($customer_list);
			} 
			foreach($branch_list as $branch) {  
				$total_paid = 0;
				$penalty_amount = 0;
				$group_count = Group::where('branch_id','=',$branch->id)->count();
				$running_groups_count += $group_count;
				$group_vacant = Group::where(array('branch_id'=>$branch->id,'is_filled'=>1))->count();
				$vacant_groups_count += $group_vacant;
				$new_group_count = Group::where('branch_id','=',$branch->id)->whereBetween('starting_date',[$first_day_this_month,$last_day_this_month])->count();
				$new_groups_count += $new_group_count;
				$bidding_oustanding = BiddingDetail::where('branch_id','=',$branch->id)->sum('pending_release');
				$total_bidding_oustanding += $bidding_oustanding;
				$this_month_collections = Receipt::selectRaw('sum(received_amount) as total_paid, sum(penalty) as penalty_amount')->where('branch_id','=',$branch->id)->whereBetween('receipt_date',[$first_day_this_month,$last_day_this_month])->get();
				$total_paid = $this_month_collections[0]->total_paid;
				$penalty_amount = $this_month_collections[0]->penalty_amount;
				$current_month_collection += $total_paid + $penalty_amount;
				$group_ato = Group::where(array('branch_id'=>$branch->id,'group_status'=>0,'is_first_auction_complete'=>0))->pluck('scheme_id');
				foreach($group_ato as $ato) {  
					$scheme_id = $ato;  
					$chitvalue = Scheme::where('id','=',$scheme_id)->get();  
					if(count($chitvalue)>0) { 
						$auction_turn_over += $chitvalue[0]->chit_value;
					}
				} 
				$bidding_oustanding_monthly = BiddingDetail::where('branch_id','=',$branch->id)->sum('pending_release');
				$monthly_bidding_outstanding += $bidding_oustanding_monthly; 
				$bidding_amount_monthly = BiddingDetail::where('branch_id','=',$branch->id)->sum('bidding_amount');
				$monthly_bidding_amount += $bidding_amount_monthly;	 
				$bidding_payable_monthly = BiddingDetail::where('branch_id','=',$branch->id)->sum('customer_released');
				$monthly_bidding_payable += $bidding_payable_monthly;	
			}
			$dashboard['running_groups_count'] = $running_groups_count;
			$dashboard['vacant_groups_count'] = $vacant_groups_count;
			$dashboard['new_groups_count'] = $new_groups_count;
			$dashboard['customers_count'] = $customers_count;
			$dashboard['total_bidding_oustanding'] = $total_bidding_oustanding;
			$dashboard['current_month_collection'] = $current_month_collection;
			$dashboard['auction_turn_over'] = $auction_turn_over;
			$dashboard['monthly_bidding_outstanding'] = $monthly_bidding_outstanding;
			$dashboard['monthly_bidding_amount'] = $monthly_bidding_amount;
			$dashboard['monthly_bidding_payable'] = $monthly_bidding_payable;
			$dashboard['collection_outstanding'] = 0;
            $response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $dashboard;
            return response()->json($response,200);
		}
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        } 
    }
	
	// public function accounts_report(Request $request) {
    	// try { 
        	// $cond = [];
            // $end_date='';
            // $start_date = '';
        	// if(isset($request->branch_id)){
        		// $cond['branch_id'] = $request->branch_id; 
        	// }
            // $receipt = Receipt::selectRaw('*,sum(received_amount) as total_paid,sum(penalty) as penalty_amount')->where($cond)->groupby('payment_type_id')->get();
          	// $inst_det = [];
		  	// foreach($receipt as $receipts) {
			  	// $accounts = [];
				// if($receipts->payment_type_id == 1) {
					// $accounts['total_paid_cash'] = $receipts->total_paid;
					// $accounts['total_paid_penalty_cash'] = $receipts->penalty_amount;
				// } 
				// else if($receipts->payment_type_id = 2) {
					// $accounts['total_paid_cheque'] = $receipts->total_paid;
					// $accounts['total_paid_penalty_cheque'] = $receipts->penalty_amount;
				// } 
				// else if($receipts->payment_type_id = 3) {
				    // $accounts['total_paid_dd'] = $receipts->total_paid;
				    // $accounts['total_paid_penalty_dd'] = $receipts->penalty_amount;
				// } 
				// else if($receipts->payment_type_id = 4) { 
				    // $accounts['total_paid_rtgs'] = $receipts->total_paid;
				    // $accounts['total_paid_penalty_rtgs'] = $receipts->penalty_amount;
				// } 
				// else if($receipts->payment_type_id = 5) { 
				    // $accounts['total_paid_card'] = $receipts->total_paid;
				    // $accounts['total_paid_penalty_card'] = $receipts->penalty_amount;
				// }
		  	// }
            // $response['status'] = 'Success';
            // $response['msg'] = "";
            // $response['data'] = $receipt;
            // return response()->json($response,200);
        // }
        // catch(\Exception $e) {
            // $response['status'] = 'Error';
            // $response['msg'] = \Lang::get('api.global_error');
            // return response()->json($response, 401);
        // }
    // }
	
	public function get_commitment_receipt_details(Request $request) {
        try {
            $cr_date = date("Y-m-d");
            //enrollment id
            $recdate = '';
            $s_date = ($request->recdate['year']).'/'.sprintf('%02d',($request->recdate['month'])).'/'.sprintf('%02d',($request->recdate['day']));
        	$rec_date = date('Y-m-d',strtotime($s_date));
        	//$rec_date = $request->recdate;
            $enrl_id = $request->enrolid;
            if($rec_date == $cr_date) 
            {			
	            $chit_details = $chit_details = ChitDetail::where('id','=',$enrl_id)->orderBy('id','desc')->get();	
				$response_data = [];
				foreach($chit_details as $cd) { 
					$enrollment_id = $cd->id;
			        $branch_id = $cd->branch_id;
			        $customer_id = $cd->customer_id;
					$cd->scheme_info = isset($cd->scheme_det) ? ($cd->scheme_det) : [];
					$slab_types = SlabDue::where('id',$cd->slab_id)->get();   
					$slab_name = isset($slab_types[0]->slab_name) ? ($slab_types[0]->slab_name) : "";
					$cd->slab_name = $slab_name;
					//total_installment_amount
					$total_installment_amount = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->sum('due_amount');
					$cd->overall_installment_amount = $total_installment_amount;
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = isset($receipt[0]->total_paid) ? ($receipt[0]->total_paid) : 0;
					$bonus_amount = isset($receipt[0]->bonus_amount) ? ($receipt[0]->bonus_amount) : 0;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;
					//pending_amount 				
					$pending_amount = $total_installment_amount - $paid_amount;
					$cd->overall_pending_amount = $pending_amount;
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   	//auction wise details 
					$auc_wise_inst_amts = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->get();
					$i=0;
					$install_det = [];
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0; 
					$inst_amt = 0;
					$penalty =0;
					$bonus =0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
						$cust_det = [];
						$inst_amt = isset($auc_wise_inst_amt->due_amount) ? ($auc_wise_inst_amt->due_amount) : 0;
	                    $auction_id = isset($auc_wise_inst_amt->id) ? ($auc_wise_inst_amt->id) : 0;
	                    $installment_no  = isset($auc_wise_inst_amt->installment_no) ? ($auc_wise_inst_amt->installment_no) : 0;
	                    $auc_date_inst  = isset($auc_wise_inst_amt->auction_date) ? ($auc_wise_inst_amt->auction_date) : 0;
	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id,'auction_id'=>$auction_id, 'status' => 1])->get();
	                    $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) {
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = isset($cus_rec_amt[0]->total_paid) ? ($cus_rec_amt[0]->total_paid) : 0;
						  	$amount_paid_bonus = isset($cus_rec_amt[0]->bonus_amount) ? ($cus_rec_amt[0]->bonus_amount) : 0;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
						}
						
						// Bonus days and percentage by heirrachy over all 
						if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
						{
						  $bonus_days = $cd->customer_det->bonus_days;
						  $bonus_percentage = $cd->customer_det->customer_bonus;
						}    					
						else if($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
						{
						  $bonus_days = $cd->branch_det->bonus_days;
						  $bonus_percentage = $cd->branch_det->bonus;              
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 			
						
						$bid = CommitmentAuction::Where('enrollment_id',$enrollment_id)->max('id');
						$check_auction = CommitmentAuction::where('id',$bid)->get();
						$max_auction_date = isset($check_auction[0]->auction_date) ? ($check_auction[0]->auction_date) : "0000-00-00";

						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$cr_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 			
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
						  	$bonus =0;
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
							$bonus  =0;
						  	$cust_det['bonus'] = 0;
						}
						
					// Penalty Calculation installment wise
	                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
	                {
	                  $inst_penalty_days = $cd->customer_det->penalty_days;
	                  $inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
	                }                
	                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
	                {
	                  $inst_penalty_days = $cd->branch_det->penalty_days;
	                  $inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
	                }
	                else
	                {
	                  $inst_penalty_days = "0";
	                  $inst_penalty_percentage = "0";
	                }
	                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
	                $pend_days_int = 0;    
	                $cust_det['penalty_amounts'] = 0;	

	                if($pending_days > $inst_penalty_days)
	                {     		
	                  $branch=Branch::where('id',$cd->branch_id)->get();
	                  if($cd->prized_status==0)
	                  {
	                    $non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
	                    $pend_days_int = $pending_days * ($non_prize/ 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  } 
	                  else
	                  {               
	                    $prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
	                    $pend_days_int = $pending_days * ($prize / 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  }                        		  
	                }    
					$cust_det['installment_no'] = $installment_no;
					$cust_det['inst_amt_wise'] = round($inst_amt);
					$cust_det['amount_paid'] = $amount_paid;
					$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
					$cust_det['pending_days'] = $pending_days;
					$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
					$cust_det['bonus_inst_wise'] = 0;
					$cust_det['penalty_inst_wise'] = 0;
					$cust_det['discount_inst_wise'] = 0;
					$cust_det['discount_penalty_wise'] = 0;
					$cust_det['auction_id'] = $auction_id;
					if($ins_amt_wise_rec>0) {
						array_push($install_det,$cust_det);
					} 
				    $total_penalty_amounts += $penalty;
					$total_bonus_amounts += $bonus;
				    }; 
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$cd->pending_details = $install_det;
					if($cd) {
						array_push($response_data,$cd);
					}
				}
			} else if($rec_date != $cr_date)  
			{  
				$chit_details = $chit_details = ChitDetail::where('id','=',$enrl_id)->orderBy('id','desc')->get();	
				$response_data = [];
				foreach($chit_details as $cd) { 
					$enrollment_id = $cd->id;
			        $branch_id = $cd->branch_id;
			        $customer_id = $cd->customer_id;
					$cd->scheme_info = isset($cd->scheme_det) ? ($cd->scheme_det) : [];
					$cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
					//total_installment_amount
					$total_installment_amount = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->sum('due_amount');
					$cd->overall_installment_amount = $total_installment_amount;
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = isset($receipt[0]->total_paid) ? ($receipt[0]->total_paid) : 0;
					$bonus_amount = isset($receipt[0]->bonus_amount) ? ($receipt[0]->bonus_amount) : 0;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;
					//pending_amount 				
					$pending_amount = $total_installment_amount - $paid_amount;
					$cd->overall_pending_amount = $pending_amount;
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   	//auction wise details 
					$auc_wise_inst_amts = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->get();
					$i=0;
					$install_det = [];
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0; 
					$inst_amt = 0;
					$penalty =0;
					$bonus =0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
						$cust_det = [];
						$inst_amt = isset($auc_wise_inst_amt->due_amount) ? ($auc_wise_inst_amt->due_amount) : 0;
	                    $auction_id = isset($auc_wise_inst_amt->id) ? ($auc_wise_inst_amt->id) : 0;
	                    $installment_no  = isset($auc_wise_inst_amt->installment_no) ? ($auc_wise_inst_amt->installment_no) : 0;
	                    $auc_date_inst  = isset($auc_wise_inst_amt->auction_date) ? ($auc_wise_inst_amt->auction_date) : 0;
	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id,'auction_id'=>$auction_id, 'status' => 1])->get();
	                    $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) {
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = isset($cus_rec_amt[0]->total_paid) ? ($cus_rec_amt[0]->total_paid) : 0;
						  	$amount_paid_bonus = isset($cus_rec_amt[0]->bonus_amount) ? ($cus_rec_amt[0]->bonus_amount) : 0;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
						}
						// Bonus days and percentage by heirrachy over all 
						$up_branch = UpdateBranch::where('branch_id',$branch_id)->where('edit_date','<=',$rec_date)->max('id');			
						$update_branches = UpdateBranch::where('id',$up_branch)->get();
						if(count($update_branches)>0)
						{
							$branch_bonus_days = isset($update_branches[0]->bonus_days) ? ($update_branches[0]->bonus_days) : 0;
							$branch_bonus_percentage = isset($update_branches[0]->bonus) ? ($update_branches[0]->bonus) : 0;
							$branch_penalty_days = isset($update_branches[0]->penalty_days) ? ($update_branches[0]->penalty_days) : 0;
		                	$branch_penalty_percentage = isset($update_branches[0]->branch_wise_penalty) ? ($update_branches[0]->branch_wise_penalty) : 0;
						}
						else
						{
							$branch_bonus_days = 0;
							$branch_bonus_percentage = 0;
							$branch_penalty_days =0;
							$branch_penalty_percentage =0;
						}						
							
						// Customer - old bonus, penalty calculation
						$up_customer = UpdateCustomer::where('update_customer_id',$customer_id)->where('edit_date','<=',$rec_date)->max('id');
						$update_customers = UpdateCustomer::where('id',$up_customer)->get(); 
						if(count($update_customers)>0)
						{
							$cust_bonus_days = isset($update_customers[0]->bonus_days) ? ($update_customers[0]->bonus_days) : 0;
							$cust_bonus_percentage = isset($update_customers[0]->customer_bonus) ? ($update_customers[0]->customer_bonus) : 0;
							$cust_penalty_days = isset($update_customers[0]->penalty_days) ? ($update_customers[0]->penalty_days) : 0;
							$cust_penalty_percentage = isset($update_customers[0]->customer_penalty_interest) ? ($update_customers[0]->customer_penalty_interest) : 0;
						}
						else
						{	
							$cust_bonus_days =0;
							$cust_bonus_percentage = 0;
							$cust_penalty_days = 0;
							$cust_penalty_percentage =0;
						}

						// Bonus days and percentage by heirrachy over all 
						if($cust_bonus_days!=0 && $cust_bonus_percentage!=0)
						{
						  $bonus_days = $cust_bonus_days;
						  $bonus_percentage = $cust_bonus_percentage;
						}    
						elseif($branch_bonus_days!=0 && $branch_bonus_percentage!=0)
						{
						  $bonus_days = $branch_bonus_days;
						  $bonus_percentage = $branch_bonus_percentage;
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 		
						
						$bid = CommitmentAuction::Where('enrollment_id',$enrollment_id)->max('id');
						$check_auction = CommitmentAuction::where('id',$bid)->get();
						$max_auction_date = isset($check_auction[0]->auction_date) ? ($check_auction[0]->auction_date) : "0000-00-00";
						
						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$cr_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 			
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
						  	$bonus =0;
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
							$bonus  =0;
						  	$cust_det['bonus'] = 0;
						}						
						
					// Penalty Calculation installment wise
	                if($cust_penalty_days!=0 )
	                {
	                  	$inst_penalty_days = $cust_penalty_days;
	                  	$inst_penalty_percentage = $cust_penalty_percentage;
	                }	                
	                elseif($branch_penalty_days!=0)
	                {
	                 	$inst_penalty_days = $branch_penalty_days;
	                  	$inst_penalty_percentage = $branch_penalty_percentage;
	                }
	                else
	                {
	                  	$inst_penalty_days = "0";
	                  	$inst_penalty_percentage = "0";
	                }
					
	                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
	                $pend_days_int = 0;    
	                $cust_det['penalty_amounts'] = 0;	

	                if($pending_days > $inst_penalty_days)
	                {     	
	                  	if($cd->prized_status==0)
	                  	{
	                    	$non_prize = isset($update_branches[0]->non_prize_subscriber_penalty) ? ($update_branches[0]->non_prize_subscriber_penalty) : 0;
	                    	$pend_days_int = $pending_days * ($non_prize/ 100);
	                    	$current_month_days = date('t'); 
	                    	$total_days_int = $pend_days_int / $current_month_days;
	                    	$penalty = $total_days_int * $ins_amt_wise_rec;
	                    	$cust_det['penalty_amounts'] = strval(round($penalty));
	                  	} 
	                  	else
	                  	{               
	                    	$prize = isset($update_branches[0]->prize_subscriber_penalty) ? ($update_branches[0]->prize_subscriber_penalty) : 0;
	                    	$pend_days_int = $pending_days * ($prize / 100);
	                    	$current_month_days = date('t'); 
	                    	$total_days_int = $pend_days_int / $current_month_days;
	                    	$penalty = $total_days_int * $ins_amt_wise_rec;
	                    	$cust_det['penalty_amounts'] = strval(round($penalty));
	                  	}                        		  
	                }      
					
					$cust_det['installment_no'] = $installment_no;
					$cust_det['inst_amt_wise'] = round($inst_amt);
					$cust_det['amount_paid'] = $amount_paid;
					$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
					$cust_det['pending_days'] = $pending_days;
					$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
					$cust_det['bonus_inst_wise'] = 0;
					$cust_det['penalty_inst_wise'] = 0;
					$cust_det['discount_inst_wise'] = 0;
					$cust_det['discount_penalty_wise'] = 0;
					$cust_det['auction_id'] = $auction_id;
					if($ins_amt_wise_rec>0) {
						array_push($install_det,$cust_det);
					} 
				    $total_penalty_amounts += $penalty;
					$total_bonus_amounts += $bonus;
				    }; 
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$cd->pending_details = $install_det;
					if($cd) {
						array_push($response_data,$cd);
					}
				}
			}
			$response['status'] = 'Success';
			$response['msg'] = "";
			$response['data'] = $response_data;
			return response()->json($response,200);
		}
		catch(\Exception $e) { 
			$response['status'] = 'Error';
			$response['msg'] = \Lang::get('api.global_error');
			$response['msg'] = $e;
			return response()->json($response, 401);
		}
	}

	public function store_bp_adjust_receipt(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required',
            'branch_id' => 'required',
            'customer_id' => 'required',
            'group_id' => 'required',
			'employee_id' => 'required',
			'enrollment_id'=> 'required',
			'adjust_id'=> 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
				if(isset($request->Installments)) 
				{  
					$count = count($request->Installments);
					$receipt_no = '';
					for($i=0; $i<$count; $i++)
					{	
						$receipt = new Receipt(); 
						$receipt->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
						$receipt->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
						$receipt->other_branch = isset($request->other_branch) ? ($request->other_branch) : 0;
						$receipt->customer_id = isset($request->customer_id) ? ($request->customer_id) : 0;
						$receipt->enrollment_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
						$receipt->group_id = isset($request->group_id) ? ($request->group_id) : 0;
						$receipt->ticket_no = isset($request->ticket_no) ? ($request->ticket_no) : '';
						$receipt->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0;
						$employee_branch_id = Employee::where('id','=',$receipt->employee_id)->pluck('branch_id');
						$receipt->employee_branch_id = $employee_branch_id[0];
						$receipt->adjust_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
						$receipt->amount = isset($request->amount) ? ($request->amount) : 0;
						if(isset($request->receipt_date)) {
							$r_date = ($request->receipt_date['year']).'-'.sprintf('%02d',($request->receipt_date['month'])).'-'.sprintf('%02d',($request->receipt_date['day'])); 
							$receipt->receipt_date = date('Y-m-d',strtotime($r_date)); 
						}
						else {
							$receipt->receipt_date = '0000-00-00';
						}
						$receipt->type_of_collection = 'b.p.adj.receipt';
						$receipt->payment_type_id = 6;
						$receipt->receipt_mode = 'ERP';
						$receipt->receipt_time = isset($request->receipt_time) ? ($request->receipt_time) : '00:00:00';
						if(isset($request->accounts_date)) {
							$a_date = ($request->accounts_date['year']).'-'.sprintf('%02d',($request->accounts_date['month'])).'-'.sprintf('%02d',($request->accounts_date['day'])); 
							$receipt->accounts_date = date('Y-m-d',strtotime($a_date)); 
						}
						else {
							$receipt->accounts_date = date('Y-m-d');
						}
						$receipt->printed = isset($request->printed) ? ($request->printed) : 0;
						$receipt->auction_id = isset($request->Installments[$i]['auction_id']) ? $request->Installments[$i]['auction_id'] : '';
						$receipt->installment_no = isset($request->Installments[$i]['installment_no']) ? $request->Installments[$i]['installment_no'] : '';
						$receipt->pending_days = isset($request->Installments[$i]['pending_days']) ? $request->Installments[$i]['pending_days'] : '';
						$receipt->penalty = isset($request->Installments[$i]['penalty_inst_wise']) ? $request->Installments[$i]['penalty_inst_wise'] : '';
						$receipt->bonus = isset($request->Installments[$i]['bonus_inst_wise']) ? $request->Installments[$i]['bonus_inst_wise'] : '';
						$receipt->discount = isset($request->Installments[$i]['discount_inst_wise']) ? $request->Installments[$i]['discount_inst_wise'] : '';
						$receipt->received_amount = isset($request->Installments[$i]['received_amount']) ? $request->Installments[$i]['received_amount'] : '';
						$receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
						$receipt->created_by = isset($request->created_by) ? ($request->created_by) : 0;
						$receipt->status = isset($request->status) ? ($request->status) : 1;
						if($receipt->amount > 0)
						{
							if($receipt->penalty !=0 || $receipt->received_amount !=0) {
								if($receipt->save()) {								
									if($receipt_no=='') {
										$receipt_no = 'RCPT-'.sprintf('%05d', $receipt->id);
									}
									$receipt->receipt_no = $receipt_no;
									$receipt->save();
								}
							}							
						}
						else {
		                    $response['status']="Error";
		                    $response['msg']=\Lang::get('api.received_amt_not_found');
		                    return response()->json($response,200);
		                }
					}
					$bidding_id = BiddingDetail::where('enrollment_id','=',$request->enrollment_id)->get();
					$bidding = BiddingDetail::find($bidding_id[0]->id);
					$pending_release = $bidding->pending_release;
					$total_release = $bidding->total_release_amount;
					$pending_due_amount = $bidding->pending_due_amount;
					$receipt_amount = $receipt->amount;
					$bidding->pending_release = $pending_release - $receipt_amount;
					$bidding->total_release_amount = $total_release - $receipt_amount;
					$bidding->pending_due_amount = $pending_due_amount + $receipt_amount;
					$bidding->save();								
					$response['status'] = 'Success';
					$response['msg'] = \Lang::get('api.success_receipt_added');
					return response()->json($response,200);
	            }
				else {
					$response['status'] = 'Error';
					$response['msg'] = \Lang::get('api.no_data_found');
					return response()->json($response, 401);
				}
			}
			catch(\Exception $e) {
				$response['status'] = 'Error';
				$response['msg'] = \Lang::get('api.global_error');
				return response()->json($response, 401);
			}
		}
	}


    Public function get_prized_customer_details(Request $request)
    {
    	try
    	{
    		$cond = $request->conditions;  // group_id
    		$bidding = BiddingDetail::where($cond)->where('customer_id','!=',1)->where('pending_release','!=',0)->get();
    		$response_data = [];
    		foreach($bidding as $bid)
    		{   
    			$bid->customer_info = ($bid->customer_det) ? ($bid->customer_det) : [];
    			$bid->enrollment_info = ($bid->enrollment_det) ? ($bid->enrollment_det) : [];  
    		}    		
			$response['status'] = 'Success';
			$response['msg'] = "";
			$response['data'] = $bidding;
			return response()->json($response,200);    		
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    public function get_pending_release_amount(Request $request)
    {
    	try
    	{
    		$cond = $request->conditions;  // enrollment_id
    		$bid_pending_release = BiddingDetail::where($cond)->get();    		
			$response_data['pending_release_amount'] = isset($bid_pending_release[0]->pending_release) ? ($bid_pending_release[0]->pending_release) : 0;
			if($bid_pending_release)
			{
				$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);
			}    		
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    public function store_advance_receipt(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required',
            'branch_id' => 'required',
            'customer_id' => 'required',
            'employee_id' => 'required',
            'enrollment_id'=> 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
                $advance_receipt = new AdvanceReceipt(); 
                $receipt_no =''; 
                $advance_receipt->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
                $advance_receipt->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
                $advance_receipt->customer_id = isset($request->customer_id) ? ($request->customer_id) : 0;
                $advance_receipt->enrollment_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
                $advance_receipt->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0;
                $employee_branch_id = Employee::where('id','=',$advance_receipt->employee_id)->pluck('branch_id');
                $advance_receipt->employee_branch_id = $employee_branch_id[0];
                $advance_receipt->receipt_amount = isset($request->receipt_amount) ? ($request->receipt_amount) : 0;
                if(isset($request->receipt_date)) {
                    $r_date = ($request->receipt_date['year']).'-'.sprintf('%02d',($request->receipt_date['month'])).'-'.sprintf('%02d',($request->receipt_date['day'])); 
                    $advance_receipt->receipt_date = date('Y-m-d',strtotime($r_date)); 
                }
                else {
                    $advance_receipt->receipt_date = '0000-00-00';
                }
                $advance_receipt->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;
                $advance_receipt->debit_to   = isset($request->debit_to ) ? ($request->debit_to ) : '';
                $advance_receipt->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : '';
                if(isset($request->cheque_date)) {
                    $c_date = ($request->cheque_date['year']).'-'.sprintf('%02d',($request->cheque_date['month'])).'-'.sprintf('%02d',($request->cheque_date['day'])); 
                    $advance_receipt->cheque_date = date('Y-m-d',strtotime($c_date)); 
                }
                else {
                    $advance_receipt->cheque_date = '0000-00-00';
                }
                if(isset($request->cheque_clear_return_date)) {
                    $cc_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
                    $advance_receipt->cheque_clear_return_date = date('Y-m-d',strtotime($cc_date)); 
                }
                else {
                    $advance_receipt->cheque_clear_return_date = '0000-00-00';
                }
                $advance_receipt->bank_name_id = isset($request->bank_id) ? ($request->bank_id) : 0;
                $advance_receipt->branch_name = isset($request->branch_name) ? ($request->branch_name) : '';
                $advance_receipt->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0;
                if(isset($request->transaction_date)) {
                    $t_date = ($request->transaction_date['year']).'-'.sprintf('%02d',($request->transaction_date['month'])).'-'.sprintf('%02d',($request->transaction_date['day'])); 
                    $advance_receipt->transaction_date = date('Y-m-d',strtotime($t_date)); 
                }
                else {
                    $advance_receipt->transaction_date = '0000-00-00';
                }  
                $advance_receipt->receipt_mode = "ERP";
                $advance_receipt->receipt_time = isset($request->receipt_time) ? ($request->receipt_time) : '00:00:00';
                $advance_receipt->printed = isset($request->printed) ? ($request->printed) : 0;
                $advance_receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
                $advance_receipt->created_by = isset($request->created_by) ? ($request->created_by) : 0;
                $advance_receipt->status = isset($request->status) ? ($request->status) : 1;
                if($request->payment_type_id == 2){
                    $advance_receipt->status = isset($request->status) ? ($request->status) : 2;
                }
                if($advance_receipt->receipt_amount >0) {
                    if($advance_receipt->save()) {
						
                        if($receipt_no=='') {
                            $receipt_no = 'ADV-'.sprintf('%05d', $advance_receipt->id);
                        }                        
                        $advance_receipt->receipt_no = $receipt_no; 
						
						
                        $advance_receipt->save();
						$advance_receipt->group_name = isset($advance_receipt->enrollment_det->group_det) ? ($advance_receipt->enrollment_det->group_det->group_name.'/'. $advance_receipt->enrollment_det->ticket_no) : "";
						$group_name = $advance_receipt->group_name;
									$receiver = isset($advance_receipt->customer_det->mobile_no) ? ($advance_receipt->customer_det->mobile_no) : ''; 
									$ticket_no = isset($advance_receipt->enrollment_det->ticket_no) ? ($advance_receipt->enrollment_det->ticket_no) : [];
									//$receiver = "7418470181";
		$receipt_receipt_date = $advance_receipt->receipt_date;
		$time = date("h:i:sa");
$msg = "Dear Customer, Your payment details
Date: $receipt_receipt_date Time: $time
Group name: $group_name
Ticket no: $ticket_no
Receipt No: $advance_receipt->receipt_no
Receipt Amount: $advance_receipt->receipt_amount
Balance Amount: $advance_receipt->balance_amount
For complaints call 04142-267555
Thanks & Regards
TNV Chit Funds Pvt Ltd
Neyveli-607801";
	$msg1=$msg;
		
		$msg_split = str_split($msg,456);
		for($i=0;$i<sizeof($msg_split);$i++)
		{
		    
				$msg_temp =  urlencode( $msg_split[$i] );
            	$ch = curl_init("https://www.instaalerts.zone/SendSMS/sendmsg.php?uname=TNVCFPtr&pass=Abc@321&send=TNVCFP&dest=$receiver&msg=$msg_temp");
            
            	
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				  $result = curl_exec($ch);
				curl_close($ch);
		}	
						
                            $response['status'] = 'Success';
                            $response['msg'] = \Lang::get('api.success_advance_receipt_added');
                            return response()->json($response,200);
                        
                        
                    }
                    else {
                        $response['status']="Error";
                        $response['msg']=\Lang::get('api.global_error');
                        return response()->json($response,401);
                    } 
                }
                else {
                    $response['status']="Error";
                    $response['msg']=\Lang::get('api.received_amt_not_found');
                    return response()->json($response,200);
                }
            }            
            catch(\Exception $e) {
                $response['status'] = 'Error';
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }

    Public function get_available_advance_details(Request $request)
    {
    	try
    	{
    		$receipts = Receipt::selectRaw('*,sum(received_amount)as total_received')->where(array('enrollment_id'=>$request->enrollment_id,'payment_type_id'=>7))->get();
    		$total_received = isset($receipts[0]->total_received) ? ($receipts[0]->total_received) : "0";
    		$advance = AdvanceReceipt::selectRaw('*,sum(receipt_amount)as total_advance')->where(array('enrollment_id'=>$request->enrollment_id,'status'=>1))->get();
    		$total_advance = isset($advance[0]->total_advance) ? ($advance[0]->total_advance) : "0";
    		$response_data['available_advance'] = $total_advance - $total_received;
    		$response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $response_data;
            return response()->json($response,200);
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }
    
	public function list_advanced_receipts(Request $request) {
        try {  
            $cond = [];
            $cond1 =[];
            $start_date = '';
            $end_date = '';
            if(isset($request->tenant_id)){
				$cond['tenant_id'] = $request->tenant_id;
			}						
			if(isset($request->customer_id)){
				$cond['customer_id'] = $request->customer_id; 
			}
			if(isset($request->employee_id)){
				$cond['employee_id'] = $request->employee_id; 
			}
			if(isset($request->payment_type_id)){
				$cond['payment_type_id'] = $request->payment_type_id; 
			} 
			if(isset($request->start_date)) { 
        		$s_date = ($request->start_date['year']).'-'.sprintf('%02d',($request->start_date['month'])).'-'.sprintf('%02d',($request->start_date['day']));
        		$start_date = date('Y-m-d',strtotime($s_date)); 
        	}				
        	if(isset($request->end_date)) {
        		$s_date = ($request->end_date['year']).'-'.sprintf('%02d',($request->end_date['month'])).'-'.sprintf('%02d',($request->end_date['day'])); 
        		$end_date = date('Y-m-d',strtotime($s_date)); 
        	}  
            if(($start_date=='') && ($end_date=='')) { 
            	if($request->branch_id > 0){
					$cond1['branch_id'] = $request->branch_id;
					$receipts=AdvanceReceipt::where($cond)->where($cond1)->get();

				}
				if($request->branch_id ==0)
				{
					$receipts=AdvanceReceipt::where($cond)->get();
				}                
            }
            else if(($start_date=='') && ($end_date!='')) { 
            	if($request->branch_id > 0){
					$cond1['branch_id'] = $request->branch_id;
                	$receipts=AdvanceReceipt::where($cond)->where($cond1)->where('receipt_date', '<=', date('Y-m-d',strtotime($end_date)))->get();
                }
				if($request->branch_id ==0)
				{
					$receipts=AdvanceReceipt::where($cond)->where('receipt_date', '<=', date('Y-m-d',strtotime($end_date)))->get();
				}
            }
            else { 
                if(($start_date!='') && ($end_date=='')) {
                    $end_date = date('Y-m-d');
                }
                if($request->branch_id > 0){
					$cond1['branch_id'] = $request->branch_id;
            		$receipts=AdvanceReceipt::where($cond)->where($cond1)->whereBetween('receipt_date',[$start_date,$end_date])->get();

            	} 
            	if($request->branch_id ==0)
				{
					$receipts=AdvanceReceipt::where($cond)->whereBetween('receipt_date',[$start_date,$end_date])->get();
				}
            }  
            $i=1;      
            $grand_total = 0;    
            $tot_rcpt = 0;      
            foreach($receipts as $receipt) 
            {
                $receipt->sno = $i;
				$receipt->branch_name = isset($receipt->branch_det->branch_name) ? ($receipt->branch_det->branch_name) : '';
				$receipt->customer_name = isset($receipt->customer_det->name) ? ($receipt->customer_det->name) : '';
				$receipt->payment_type_name = isset($receipt->payment_type_det->payment_name) ? ($receipt->payment_type_det->payment_name) : '';
				$receipt->bank_name = isset($receipt->bank_det) ? ($receipt->bank_det->bank_name) : "";
                $receipt->bank_branch_name = isset($receipt->bank_det->bank_branch_name) ? ($receipt->bank_det->bank_branch_name) : '';
				$receipt->tenant_info = isset($receipt->tenant_det) ? ($receipt->tenant_det) : [];
                $receipt->scheme_info = isset($receipt->scheme_det) ? ($receipt->scheme_det) : [];
				$receipt->group_info =[];
				$receipt->enrollment_info =[];
				if(isset($receipt->enrollment_det)){
				 $receipt->enrollment_info = isset($receipt->enrollment_det) ? ($receipt->enrollment_det) : [];	
				 $receipt->group_info = isset($receipt->enrollment_det->group_det) ? ($receipt->enrollment_det->group_det) : [];	
				}
				// Group Name
				$group_name = '';
				if($receipt->enrollment_det->chit_type != 3)
				{
					$group_name = isset($receipt->enrollment_det->group_det) ? ($receipt->enrollment_det->group_det->group_name.'/'. $receipt->enrollment_det->ticket_no) : "";
				}
				if($receipt->enrollment_det->chit_type == 3)
				{
					$slab_types = SlabDue::where('id',$receipt->enrollment_det->slab_id)->get();   
		            $slab_name = isset($slab_types[0]->slab_name) ? ($slab_types[0]->slab_name) : "";
		            $group_name = "Commitment"."/".$slab_name;
				}
				
				$receipt->group_name = $group_name;
                $receipt->employee_info = isset($receipt->employee_det) ? ($receipt->employee_det) : [];	
                $receipt->collection_employee_name = isset($receipt->employee_det) ? ($receipt->employee_det->first_name.' '.$receipt->employee_det->last_name) : "";			
				// Other branch Name
				$chit_details = ChitDetail::where('id',$receipt->enrollment_id)->get();
				$receipt->collected_branch_name = isset($chit_details[0]->branch_det) ? ($chit_details[0]->branch_det->branch_name) : "";

				$receipt->receipt_date = ($receipt->receipt_date!=0) ? date('d/m/Y',strtotime($receipt->receipt_date)) : '';
				$receipt->accounts_date = ($receipt->accounts_date!=0) ? date('d/m/Y',strtotime($receipt->accounts_date)) : '';
				$receipt->cheque_date = ($receipt->cheque_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_date)) : '';
				$receipt->cheque_clear_return_date = ($receipt->cheque_clear_return_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_clear_return_date)) : '';
				$receipt->cheque_bank_info = isset($receipt->cheque_debit_to_info) ? ($receipt->cheque_debit_to_info) : [];
				$receipt->status_name =[];
				if($receipt->status == 1 )
				{
					$receipt->status_name = "Active";
				}	
				else if($receipt->status == 2 )
				{
					$receipt->status_name = "Pending";
				}		
				else if($receipt->status == 3 )
				{
					$receipt->status_name = "Return";
				}	
				else if($receipt->status == 0 )
				{
					$receipt->status_name = "In-Active";
				}else
				{
					$receipt->status_name = "-";
				}
				
                $i++;
                $receipt_amount = isset($receipt->receipt_amount) ? ($receipt->receipt_amount) : 0;
                $tot_rcpt += $receipt_amount;
            }	
            $grand_total += $tot_rcpt;
            $grand_total_format =0;
            if($grand_total > 0)
            {   
                $num = $grand_total;
                $grand_total_format = $this->moneyFormatIndia($num);  
            }            
            $response['grand_total'] = $grand_total_format;		   
			$response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $receipts;
            return response()->json($response,200);
        }
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            $response['msg'] = $e;
            return response()->json($response, 401);
        }
    }
	
	public function available_advanced_receipts(Request $request) {
        try {  
            $cond = [];
            $cond1 =[];
            if(isset($request->tenant_id)){
				$cond['tenant_id'] = $request->tenant_id;
			}
			if($request->branch_id >0 ){
				$cond1['branch_id'] = $request->branch_id;
			}
			if(isset($request->group_id)){
				$cond['group_id'] = $request->group_id; 
			}
			if(isset($request->customer_id)){
				$cond['customer_id'] = $request->customer_id; 
			}
			
            $available_advances=ChitDetail::where($cond)->where($cond1)->where('chit_type','!=',1)->where('customer_id','!=',1)->get(); 
             
            if($request->branch_id == 0 ){
				$available_advances=ChitDetail::where($cond)->where('chit_type','!=',1)->where('customer_id','!=',1)->get(); 
			}

            $i=1;  
			if(count($available_advances)>0)
			{  
                $grand_avail_advance_amt = 0;
                $tot_avail_advance = 0;     
                $grand_adj_advance_amt = 0;
                $tot_adj_advance = 0;     
                $grand_advance_amt = 0;
                $tot_advance = 0;   
	            foreach($available_advances as $available_advance) 
	            {
	                $available_advance->sno = $i;
					$available_advance->tenant_info = isset($available_advance->tenant_det) ? ($available_advance->tenant_det) : [];
	                $available_advance->branch_name = isset($available_advance->branch_det->branch_name) ? ($available_advance->branch_det->branch_name) : [];
	                $available_advance->group_name = isset($available_advance->group_det->group_name) ? ($available_advance->group_det->group_name) : [];
					$available_advance->ticket_no = isset($available_advance->ticket_no) ? ($available_advance->ticket_no) : [];
					$available_advance->customer_name = isset($available_advance->customer_det->name) ? ($available_advance->customer_det->name) : [];
					$advance_amount = AdvanceReceipt::where(array('enrollment_id'=>$available_advance->id,'status'=>1))->sum('receipt_amount');
					$advance_adjustment = Receipt::selectRaw('*,sum(received_amount) as total_paid, sum(penalty) as total_penalty')->where(array('enrollment_id'=>$available_advance->id,'payment_type_id'=>7))->get();
					$receipt_amt = isset($advance_adjustment[0]->total_paid) ? ($advance_adjustment[0]->total_paid) : 0;
					$penalty_amt = isset($advance_adjustment[0]->total_penalty) ? ($advance_adjustment[0]->total_penalty) : 0;
					$total_amount = $receipt_amt + $penalty_amt;
					$available_advance_amount = $advance_amount - $total_amount;
					$available_advance->available_advance_amount = $available_advance_amount;
					$available_advance->advance_amount = $advance_amount;
					$available_advance->advance_adjustment = $total_amount;
	                $tot_advance += $advance_amount;
	                $tot_adj_advance += $total_amount;
	                $tot_avail_advance += $available_advance_amount;
	                $i++;
	            } 
	            $grand_avail_advance_amt +=$tot_avail_advance;
	            $grand_adj_advance_amt +=$tot_adj_advance;
	            $grand_advance_amt +=$tot_advance;

	            $avail_advance_format =0;
	            $adj_advance_format =0;
	            $grand_advance_format =0;

	            if($grand_avail_advance_amt > 0)
	            {   
	                $num = $grand_avail_advance_amt;
	                $avail_advance_format = $this->moneyFormatIndia($num);  
	            } 
	            if($grand_adj_advance_amt > 0)
	            {   
	                $num = $grand_adj_advance_amt;
	                $adj_advance_format = $this->moneyFormatIndia($num);  
	            }
	            if($grand_advance_amt > 0)
	            {   
	                $num = $grand_advance_amt;
	                $grand_advance_format = $this->moneyFormatIndia($num);  
	            }
	            $response['grand_avail_advance_amt'] = $avail_advance_format;
	            $response['grand_adj_advance_amt'] = $adj_advance_format;
	            $response['grand_advance_amt'] = $grand_advance_format;
	            $response['status'] = 'Success';
	            $response['msg'] = "";
	            $response['data'] = $available_advances;
	            return response()->json($response,200);
			}
			else {
				$available_advances = [];
	            $response['grand_avail_advance_amt'] = 0;
	            $response['grand_adj_advance_amt'] = 0;
	            $response['grand_advance_amt'] = 0;
                $response['status'] = 'Success';
				$response['msg'] = \Lang::get('api.no_data_found');
				$response['data'] = $available_advances;
		        return response()->json($response, 200);	
			}
        }
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

	
	 Public function get_auctionwise_receipt(Request $request)
    {
    	try
    	{ // id
			$cond = $request->conditions; 
			$response_data = [];
            $chit = ChitDetail::where($cond)->get();
            foreach($chit as $c)
            {            	
	            $group_id = isset($c->group_id) ? ($c->group_id) : 0;
	            $enrollment_id = isset($c->id) ? ($c->id) : 0; 
	            if($c->chit_type != 3)
	            {
		            $auction_details = BiddingDetail::where('group_id',$group_id)->orderBy('id','asc')->get();
					foreach($auction_details as  $auction_detail) 
					{				
						$install_det = [];					
						$auction_id = $auction_detail->id;
						$receipt_details = Receipt::where('auction_id',$auction_id)->where('enrollment_id',$enrollment_id)->orderBy('id','asc')->get();
					    foreach($receipt_details as $receipt_detail) {
							$rect_det = [];	
							$rect_det['receipt_date'] = $receipt_detail->receipt_date;
							$rect_det['receipt_no'] = $receipt_detail->receipt_no;
							$rect_det['receipt_amount'] = $receipt_detail->received_amount;
							$rect_det['receipt_penalty'] = $receipt_detail->penalty;
							$rect_det['receipt_bonus'] = $receipt_detail->bonus;
							$rect_det['receipt_divident'] = $receipt_detail->divident;
							$rect_det['net_collected_receipt_amount'] = $receipt_detail->received_amount + $receipt_detail->penalty;
							array_push($install_det,$rect_det);
						}
						$auction_detail->auction_details = $install_det;
						array_push($response_data,$auction_detail);
					}             	
	            }
	            if($c->chit_type ==3)
	            {
	            	$auction_details = CommitmentAuction::where('enrollment_id',$enrollment_id)->orderBy('id','asc')->get();
					foreach($auction_details as  $auction_detail) 
					{				
						$install_det = [];					
						$auction_id = $auction_detail->id;
						$receipt_details = Receipt::where('auction_id',$auction_id)->where('enrollment_id',$enrollment_id)->orderBy('id','asc')->get();
					    foreach($receipt_details as $receipt_detail) {
							$rect_det = [];	
							$rect_det['receipt_date'] = $receipt_detail->receipt_date;
							$rect_det['receipt_no'] = $receipt_detail->receipt_no;
							$rect_det['receipt_amount'] = $receipt_detail->received_amount;
							$rect_det['receipt_penalty'] = $receipt_detail->penalty;
							$rect_det['receipt_bonus'] = $receipt_detail->bonus;
							$rect_det['receipt_divident'] = $receipt_detail->divident;
							$rect_det['net_collected_receipt_amount'] = $receipt_detail->received_amount + $receipt_detail->penalty;
							array_push($install_det,$rect_det);
						}
						$auction_detail->auction_details = $install_det;
						array_push($response_data,$auction_detail);
					}
            	}
            }
			
			$response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $response_data;
            return response()->json($response,200);
			  }
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }


    public function get_enrol_details_divident_cancel(Request $request) {
        try {
            //enrollment id
            $cr_date = date("Y-m-d");
            //enrollment id
            $recdate = '';
            $s_date = ($request->recdate['year']).'/'.sprintf('%02d',($request->recdate['month'])).'/'.sprintf('%02d',($request->recdate['day']));
        	$rec_date = date('Y-m-d',strtotime($s_date));
            $enrl_id = $request->enrolid;
            if($rec_date == $cr_date) 
            {	 
	            $chit_details = ChitDetail::where('id',$enrl_id)->orderBy('id','desc')->get();		
				$response_data = [];			
				foreach($chit_details as $cd) {
					$enrollment_id = $cd->id;
			        $group_id = $cd->group_id;
			        $cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
			        $customer_id = $cd->customer_id;
					$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
					$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
					//total_installment_amount
					$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');				
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = $receipt[0]->total_paid;
					$bonus_amount = $receipt[0]->bonus_amount;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;				

					//last auction details
					$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
					$current_inst_amt = BiddingDetail::where('Id','=',$max_bid_id)->pluck('current_installment_amount');
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   //auction wise details 
					$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
					$i=0;
					$install_det = [];					
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0;
					$total_divid_amount =0 ;
					$total_divi_amt = 0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
						$cust_det = [];		
						$penalty = 0;					
						$bonus = 0;	
						$divident_amt =0;	
									
						$inst_amt = $auc_wise_inst_amt->current_installment_amount;
						$divident_amt = $auc_wise_inst_amt->divident_amount;
						$auction_id = $auc_wise_inst_amt->id;
						$auc_id  = $auc_wise_inst_amt->id;
						$installment_no  = $auc_wise_inst_amt->installment_no;
						$auc_date_inst  = $auc_wise_inst_amt->auction_date;
						$rcpts = Receipt::where('enrollment_id',$enrollment_id)->where('auction_id','=',$auc_id)->where('status',1)->get();	
						$divident = 0;
						if(count($rcpts)>0)
						{							 		
							$divident = isset($rcpts[0]->cancel_dividend_amount)? ($rcpts[0]->cancel_dividend_amount) : 0;
							$total_divi_amt = $divident_amt - $divident;
						}
						else
						{
							$total_divi_amt= $total_divi_amt + $divident_amt;
						}
						$total_divide_amount = $divident_amt - $divident;
						$cd->overall_installment_amount = $total_installment_amount + $total_divi_amt;
						//pending_amount 
						$total_install_amt = $total_installment_amount + $total_divi_amt;
						$pending_amount = $total_install_amt - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;

	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();	
						 $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						 $last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) { 
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
						  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $inst_amt_no !=0) {
							// && $i==0
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							//echo $pending_days;
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = date('Y-m-d');
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							//echo $pending_days;
							$i++;
						}
						
						// Bonus days and percentage by heirrachy over all 
						if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
						{
						  $bonus_days = $cd->customer_det->bonus_days;
						  $bonus_percentage = $cd->customer_det->customer_bonus;
						}    
						elseif($cd->group_det->group_bonus_days!=0 && $cd->group_det->group_base_bonus!=0)
						{
						  $bonus_days = $cd->group_det->group_bonus_days;
						  $bonus_percentage = $cd->group_det->group_base_bonus;
						}
						elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
						{
						  $bonus_days = $cd->branch_det->bonus_days;
						  $bonus_percentage = $cd->branch_det->bonus;              
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 					
						$bid = BiddingDetail::Where('group_id',$group_id)->max('id');
						$check_auction = BiddingDetail::where('id',$bid)->get();
						$max_auction_date  =$check_auction[0]->auction_date;
						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$cr_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
						  $cust_det['bonus'] = 0;
						}
						
					// Penalty Calculation installment wise
	                if($cd->customer_det->penalty_days!=0 )
	                {
	                  $inst_penalty_days = $cd->customer_det->penalty_days;
	                  $inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
	                }
	                elseif($cd->group_det->group_penalty_days!=0)
	                {
	                  $inst_penalty_days = $cd->group_det->group_penalty_days;
	                  $inst_penalty_percentage = $cd->group_det->group_base_penalty;                  
	                }
	                elseif($cd->branch_det->penalty_days!=0 )
	                {
	                  $inst_penalty_days = $cd->branch_det->penalty_days;
	                  $inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
	                }
	                else
	                {
	                  $inst_penalty_days = "0";
	                  $inst_penalty_percentage = "0";
	                }
	                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
	                $pend_days_int = 0;    
	                $cust_det['penalty_amounts'] = 0;   
					
	                if($pending_days > $inst_penalty_days)
	                {     		
	                  $branch=Branch::where('id',$cd->branch_id)->get();
	                  if($cd->prized_status==0)
	                  {
	                    $non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
	                    $pend_days_int = $pending_days * ($non_prize/ 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  } 
	                  else
	                  {               
	                    $prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
	                    $pend_days_int = $pending_days * ($prize / 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  }                        		  
	                }    
						
						$cust_det['installment_no'] = $installment_no;
						$cust_det['inst_amt_wise'] = $inst_amt;
						$cust_det['amount_paid'] = $amount_paid;
						$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
						$cust_det['cancel_dividend_amount'] = $total_divide_amount;
						$cust_det['pending_days'] = $pending_days;
						$cust_det['bonus_inst_wise'] = 0;
						$cust_det['penalty_inst_wise'] = 0;
						$cust_det['discount_inst_wise'] = 0;
						$cust_det['discount_penalty_wise'] = 0;
						$cust_det['auction_id'] = $auction_id;
						$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus + $total_divide_amount;
						if($ins_amt_wise_rec>0) {
							array_push($install_det,$cust_det);
						}	
						$total_penalty_amounts += $penalty;
						$total_bonus_amounts += $bonus;
					};
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$response_data['total_divident_amount'] = strval(round($total_divi_amt));
					$cd->pending_details = $install_det;
					$cd->scheme_info = [];
					if(isset($cd->group_det)) {
						$cd->scheme_info = isset($cd->group_det->scheme_det) ? ($cd->group_det->scheme_det) : [];
					}
					if($cd) {
						array_push($response_data,$cd);
					}
				}
			} else if($rec_date != $cr_date) 
            {	
	            $chit_details = ChitDetail::where('id',$enrl_id)->orderBy('id','desc')->get();		
				$response_data = [];			
				foreach($chit_details as $cd) {
					$enrollment_id = $cd->id;
			        $group_id = $cd->group_id;
			        $branch_id = $cd->branch_id;
			        $cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
			        $customer_id = $cd->customer_id;
					$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
					$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
					//total_installment_amount
					$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');				
					//paid_amount
					$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
					$paid_rec_amount = $receipt[0]->total_paid;
					$bonus_amount = $receipt[0]->bonus_amount;
					$paid_amount = $paid_rec_amount + $bonus_amount;
					$cd->overall_paid_amount = $paid_amount;				

					//last auction details
					$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
					$current_inst_amt = BiddingDetail::where('Id','=',$max_bid_id)->pluck('current_installment_amount');
					//no installment paid 
					$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
				   //auction wise details 
					$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
					$i=0;
					$install_det = [];					
					$total_penalty_amounts = 0;
					$total_bonus_amounts = 0;
					$total_divid_amount =0 ;
					$total_divi_amt = 0;
					foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
						$cust_det = [];		
						$penalty = 0;					
						$bonus = 0;	
						$divident_amt =0;	
									
						$inst_amt = $auc_wise_inst_amt->current_installment_amount;
						$divident_amt = $auc_wise_inst_amt->divident_amount;
						$auction_id = $auc_wise_inst_amt->id;
						$auc_id  = $auc_wise_inst_amt->id;
						$installment_no  = $auc_wise_inst_amt->installment_no;
						$auc_date_inst  = $auc_wise_inst_amt->auction_date;
						$rcpts = Receipt::where('enrollment_id',$enrollment_id)->where('auction_id','=',$auc_id)->where('status',1)->get();	
						$divident =0;
						if(count($rcpts)>0)
						{							 		
							$divident = isset($rcpts[0]->cancel_dividend_amount)? ($rcpts[0]->cancel_dividend_amount) : 0;
							$total_divi_amt = $divident_amt - $divident;
						}
						else
						{
							$total_divi_amt= $total_divi_amt + $divident_amt;
						}
						$total_divide_amount = $divident_amt - $divident;
						$cd->overall_installment_amount = $total_installment_amount + $total_divi_amt;
						//pending_amount 
						$total_install_amt = $total_installment_amount + $total_divi_amt;
						$pending_amount = $total_install_amt - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;

	                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();	
						 $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
						 $last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
						if(empty($cus_rec_amt[0])) { 
							$amount_paid = 0;
						} 
						else {
						  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
						  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
						  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
						}
						$ins_amt_wise_rec = $inst_amt - $amount_paid;
						if($ins_amt_wise_rec !=$inst_amt && $inst_amt_no !=0) {
							// && $i==0
							$receipt_date = $last_receipt_date_inst[0];
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						} 
						else {
							$receipt_date = $auc_date_inst;
							$current_date = $rec_date;
							$datetime1 = new DateTime($receipt_date);
							$datetime2 = new DateTime($current_date);
							$interval = $datetime1->diff($datetime2);
							$pending_days = $interval->format('%a');
							$i++;
						}
						// Branch - old bonus, penalty calculation
						$up_branch = UpdateBranch::where('branch_id',$branch_id)->where('edit_date','<=',$rec_date)->max('id');			
						$update_branches = UpdateBranch::where('id',$up_branch)->get();
						if(count($update_branches)>0)
						{
							$branch_bonus_days = isset($update_branches[0]->bonus_days) ? ($update_branches[0]->bonus_days) : 0;
							$branch_bonus_percentage = isset($update_branches[0]->bonus) ? ($update_branches[0]->bonus) : 0;
							$branch_penalty_days = isset($update_branches[0]->penalty_days) ? ($update_branches[0]->penalty_days) : 0;
		                	$branch_penalty_percentage = isset($update_branches[0]->branch_wise_penalty) ? ($update_branches[0]->branch_wise_penalty) : 0;
						}
						else
						{
							$branch_bonus_days = 0;
							$branch_bonus_percentage = 0;
							$branch_penalty_days =0;
							$branch_penalty_percentage =0;
						}

						// Group - old bonus, penalty calculation
						$up_group = UpdateGroup::where('group_id',$group_id)->where('edit_date','<=',$rec_date)->max('id');			
						$update_groups = UpdateGroup::where('id',$up_group)->get();
						if(count($update_groups)>0)
						{
							$group_bonus_days = isset($update_groups[0]->group_bonus_days) ? ($update_groups[0]->group_bonus_days) : 0; 
							$group_bonus_percentage = isset($update_groups[0]->group_base_bonus) ? ($update_groups[0]->group_base_bonus) : 0;
							$group_penalty_days = isset($update_groups[0]->group_penalty_days) ? ($update_groups[0]->group_penalty_days) : 0;
							$group_penalty_percentage = isset($update_groups[0]->group_base_penalty) ? ($update_groups[0]->group_base_penalty) : 0;
						}
						else
						{
							$group_bonus_days =0;
							$group_bonus_percentage =0;
							$group_penalty_days =0;
							$group_penalty_percentage =0;
						}
						// Customer - old bonus, penalty calculation
						$up_customer = UpdateCustomer::where('update_customer_id',$customer_id)->where('edit_date','<=',$rec_date)->max('id');
						$update_customers = UpdateCustomer::where('id',$up_customer)->get();
						if(count($update_customers)>0)
						{
							$cust_bonus_days = isset($update_customers[0]->bonus_days) ? ($update_customers[0]->bonus_days) : 0;
							$cust_bonus_percentage = isset($update_customers[0]->customer_bonus) ? ($update_customers[0]->customer_bonus) : 0;
							$cust_penalty_days = isset($update_customers[0]->penalty_days) ? ($update_customers[0]->penalty_days) : 0;
							$cust_penalty_percentage = isset($update_customers[0]->customer_penalty_interest) ? ($update_customers[0]->customer_penalty_interest) : 0;
						}
						else
						{	
							$cust_bonus_days =0;
							$cust_bonus_percentage = 0;
							$cust_penalty_days = 0;
							$cust_penalty_percentage =0;
						}

						// Bonus days and percentage by heirrachy over all 
						if($cust_bonus_days!=0 && $cust_bonus_percentage!=0)
						{
						  $bonus_days = $cust_bonus_days;
						  $bonus_percentage = $cust_bonus_percentage;	
						}    
						elseif($group_bonus_days!=0 && $group_bonus_percentage!=0)
						{
						  $bonus_days = $group_bonus_days;
						  $bonus_percentage = $group_bonus_percentage;
						}
						elseif($branch_bonus_days!=0 && $branch_bonus_percentage!=0)
						{
						  $bonus_days = $branch_bonus_days;
						  $bonus_percentage = $branch_bonus_percentage;
						}    
						else
						{
						  $bonus_days = 0;
						  $bonus_percentage = 0;
						} 		
						$bid = BiddingDetail::Where('group_id',$group_id)->max('id');
						$check_auction = BiddingDetail::where('id',$bid)->get();
						$max_auction_date  =$check_auction[0]->auction_date;
						// Auction on holiday check for bonus
						$auction_on_holiday = AuctionOnHoliday::where('tenant_id','=',$cd->tenant_id)->whereBetween('holiday_date',[$max_auction_date,$rec_date])->get();
						$holiday_count = 0;
						if(count($auction_on_holiday)>0)
						{	
							$holiday_count += $auction_on_holiday->count();
						}

						$bonus_days_include_holiday = $bonus_days + $holiday_count;
						if($pending_days <=  $bonus_days_include_holiday)
						{ 							
						  if($ins_amt_wise_rec==$inst_amt)
						  { 
							$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
							$bonus = strval(round($bonus_calculation));
							$cust_det['bonus'] = $bonus;
						  }
						  else
						  {
							$cust_det['bonus'] = 0;
						  }
						}
						else
						{
						  $cust_det['bonus'] = 0;
						}
						

					// Penalty Calculation installment wise
		                if($cust_penalty_days!=0 && $cust_penalty_percentage!=0)
		                {
		                  $inst_penalty_days = $cust_penalty_days;
		                  $inst_penalty_percentage = $cust_penalty_percentage;
		                }
		                elseif($group_penalty_days!=0 && $group_penalty_percentage!=0)
		                {
		                  $inst_penalty_days = $group_penalty_days;
		                  $inst_penalty_percentage = $group_penalty_percentage;                  
		                }
		                elseif($branch_penalty_days!=0)
		                {
		                  $inst_penalty_days = $branch_penalty_days;
		                  $inst_penalty_percentage = 0;
		                }
		                else
		                {
		                  $inst_penalty_days = "0";
		                  $inst_penalty_percentage = "0";
		                }

		                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
		                $pend_days_int = 0;    
		                $cust_det['penalty_amounts'] = 0;   
						
		                if($pending_days > $inst_penalty_days)
		                {     	
		                  if($cd->prized_status==0)
		                  {
		                    $non_prize = isset($update_branches[0]->non_prize_subscriber_penalty) ? ($update_branches[0]->non_prize_subscriber_penalty) : 0;
		                    $pend_days_int = $pending_days * ($non_prize/ 100);
		                    $current_month_days = date('t'); 
		                    $total_days_int = $pend_days_int / $current_month_days;
		                    $penalty = $total_days_int * $ins_amt_wise_rec;
		                    $cust_det['penalty_amounts'] = strval(round($penalty));
		                  } 
		                  else
		                  {               
		                    $prize = isset($update_branches[0]->prize_subscriber_penalty) ? ($update_branches[0]->prize_subscriber_penalty) : 0;
		                    $pend_days_int = $pending_days * ($prize / 100);
		                    $current_month_days = date('t'); 
		                    $total_days_int = $pend_days_int / $current_month_days;
		                    $penalty = $total_days_int * $ins_amt_wise_rec;
		                    $cust_det['penalty_amounts'] = strval(round($penalty));
		                  }                        		  
		                }      
						
						$cust_det['installment_no'] = $installment_no;
						$cust_det['inst_amt_wise'] = $inst_amt;
						$cust_det['amount_paid'] = $amount_paid;
						$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
						$cust_det['cancel_dividend_amount'] = $total_divide_amount;
						$cust_det['pending_days'] = $pending_days;
						$cust_det['bonus_inst_wise'] = 0;
						$cust_det['penalty_inst_wise'] = 0;
						$cust_det['discount_inst_wise'] = 0;
						$cust_det['discount_penalty_wise'] = 0;
						$cust_det['auction_id'] = $auction_id;
						$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus + $total_divide_amount;
						if($ins_amt_wise_rec>0) {
							array_push($install_det,$cust_det);
						}	
						$total_penalty_amounts += $penalty;
						$total_bonus_amounts += $bonus;
					};
					$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
					$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
					$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
					$response_data['total_divident_amount'] = strval(round($total_divi_amt));
					$cd->pending_details = $install_det;
					$cd->scheme_info = [];
					if(isset($cd->group_det)) {
						$cd->scheme_info = isset($cd->group_det->scheme_det) ? ($cd->group_det->scheme_det) : [];
					}
					if($cd) {
						array_push($response_data,$cd);
					}
				}
			}
			$response['status'] = 'Success';
			$response['msg'] = "";
			$response['data'] = $response_data;
			return response()->json($response,200);	
		}
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

    Public function customer_ledger_running_enroll_info(Request $request)
    {
    	try
    	{ //customer_id
    		$cond = $request->conditions;
    		$chits =ChitDetail::where($cond)->get();
    		if(count($chits)>0)
    		{
	    		$i=1; 		
	    		$response_data= [];
	    		foreach($chits as $chit)
	    		{	    			
	    			// Group Details  			
	    			$running_group = Group::where('id',$chit->group_id)->where('group_status','=','0')->get();
	    			foreach($running_group as $group)
	    			{
	    				$details = [];  
						$details['sno'] = $i;
						$details['enrollment_id'] = isset($chit->id) ? ($chit->id) : 0;
						$details['group_name'] = isset($group->group_name) ? ($group->group_name) : "";
						$details['chit_value'] = isset($group->scheme_det->chit_value) ? ($group->scheme_det->chit_value) : '0'; 
						$details['auction_date'] = isset($group->auction_date) ? ($group->auction_date) : "00";				 
						if($chit->agent_type == 1)
						{
							$details['introducer_type'] = "Customer";
						}else if($chit->agent_type == 2)
						{
							$details['introducer_type'] = "Employee";
						}else if($chit->agent_type == 3)
						{
							$details['introducer_type'] = "Business Agent";
						} else if($chit->agent_type == 4)
						{
							$details['introducer_type'] = "Self Join";
						}else if($chit->agent_type == 5)
						{
							$details['introducer_type'] = "Others";
						}else
						{
							$details['introducer_type'] = ""; 
						}

						$details['introducer_name_info'] = [];
						if($chit->employee_agent_intro_det)
						{
							$details['introducer_name_info'] = isset($chit->employee_agent_intro_det) ? ($chit->employee_agent_intro_det->first_name.' '.$chit->employee_agent_intro_det->last_name) : [];
						}
						// Bidding Details - Installment count
						$bid = BiddingDetail::where('group_id',$chit->group_id)->count();
						$details['installments']  = isset($bid) ? ($bid) : 0;
						// Chit Taken
						$bid2 = BiddingDetail::where('enrollment_id',$chit->id)->where('group_id',$chit->group_id)->get();
						if(count($bid2)>0)
						{
							$details['chit_taken'] = "Yes";
						}
						else
						{
							$details['chit_taken'] = "No";
						}

						// Advance
						$adv = AdvanceReceipt::selectRaw('*,sum(receipt_amount) as total_adv')->where('enrollment_id',$chit->id)->get();
						$total_adv = isset($adv[0]->total_adv) ? ($adv[0]->total_adv) : 0;
						$details['advance'] = $total_adv;

						// Collected 
						$receipt = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus')->where('group_id',$chit->group_id)->where('enrollment_id',$chit->id)->get();
						$total_receipt = isset($receipt[0]->total_receipt) ? ($receipt[0]->total_receipt) : 0 ;
						$total_bonus = isset($receipt[0]->total_bonus) ? ($receipt[0]->total_bonus) : 0;
						$collected = $total_receipt + $total_bonus;
						$details['collected'] =  $collected;

						// To be collected
						$bid3 = BiddingDetail::selectRaw('*,sum(current_installment_amount) as total_install_amt')->where('group_id',$chit->group_id)->get();
						$install_amt = isset($bid3[0]->total_install_amt) ? ($bid3[0]->total_install_amt) : 0;
						$to_be_collected = $install_amt - $collected;
						$details['to_be_collected'] =$to_be_collected;
						array_push($response_data,$details);
	    			}				
	    		}    		
	    		$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);	 
    		}
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}    		
    }


    Public function customer_ledger_closed_enroll_info(Request $request)
    {
    	try
    	{    //customer_id
    		$cond = $request->conditions;	    			
    		$chits =ChitDetail::where($cond)->get();
    		if(count($chits)>0)
    		{ 		
    			$i=1; 
    			$response_data= [];
				foreach($chits as $chit)
				{						
					// Group Details  			
					$running_group = Group::where('id',$chit->group_id)->where('group_status','=','1')->get();
					foreach($running_group as $group)
					{	
						$details = [];  
						$details['sno'] = $i;
						$details['enrollment_id'] = isset($chit->id) ? ($chit->id) : 0;
						$details['group_name'] = isset($group->group_name) ? ($group->group_name) : "";
						$details['chit_value'] = isset($group->scheme_det->chit_value) ? ($group->scheme_det->chit_value) : '0'; 
						$details['auction_date'] = isset($group->auction_date) ? ($group->auction_date) : "00";				 
						if($chit->agent_type == 1)
						{
							$details['introducer_type'] = "Customer";
						}else if($chit->agent_type == 2)
						{
							$details['introducer_type'] = "Employee";
						}else if($chit->agent_type == 3)
						{
							$details['introducer_type'] = "Business Agent";
						} else if($chit->agent_type == 4)
						{
							$details['introducer_type'] = "Self Join";
						}else if($chit->agent_type == 5)
						{
							$details['introducer_type'] = "Others";
						}else
						{
							$details['introducer_type'] = ""; 
						}

						$details['introducer_name_info'] = [];
						if($chit->employee_agent_intro_det)
						{
							$details['introducer_name_info'] = isset($chit->employee_agent_intro_det) ? ($chit->employee_agent_intro_det->first_name.' '.$chit->employee_agent_intro_det->last_name) : [];
						}
						// Bidding Details - Installment count
						$bid = BiddingDetail::where('group_id',$chit->group_id)->count();
						$details['installments']  = isset($bid) ? ($bid) : 0;
						// Chit Taken
						$bid2 = BiddingDetail::where('enrollment_id',$chit->id)->where('group_id',$chit->group_id)->get();
						if(count($bid2)>0)
						{
							$details['chit_taken'] = "Yes";
						}
						else
						{
							$details['chit_taken'] = "No";
						}

						// Advance
						$adv = AdvanceReceipt::selectRaw('*,sum(receipt_amount) as total_adv')->where('enrollment_id',$chit->id)->get();
						$total_adv = isset($adv[0]->total_adv) ? ($adv[0]->total_adv) : 0;
						$details['advance'] = $total_adv;

						// Collected 
						$receipt = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus')->where('group_id',$chit->group_id)->where('enrollment_id',$chit->id)->get();
						$total_receipt = isset($receipt[0]->total_receipt) ? ($receipt[0]->total_receipt) : 0 ;
						$total_bonus = isset($receipt[0]->total_bonus) ? ($receipt[0]->total_bonus) : 0;
						$collected = $total_receipt + $total_bonus;
						$details['collected'] =  $collected;

						// To be collected
						$bid3 = BiddingDetail::selectRaw('*,sum(current_installment_amount) as total_install_amt')->where('group_id',$chit->group_id)->get();
						$install_amt = isset($bid3[0]->total_install_amt) ? ($bid3[0]->total_install_amt) : 0;
						$to_be_collected = $install_amt - $collected;
						$details['to_be_collected'] =$to_be_collected;	
						array_push($response_data,$details);
					}
				}				   		
	    		$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);    			
    		}   		
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    Public function cust_ledger_paid_and_pending(Request $request)
    {
    	try
    	{ //customer_id
    		$cond = $request->conditions;
    		$chit_details = ChitDetail::where($cond)->get();
    		if(count($chit_details)>0)
    		{	
    			$non_prized_install = 0;
    			$non_prized_collected = 0;
    			$prized_install = 0;
    			$prized_collected = 0;
    			$response_data['non_prized_paid'] = 0;
				$response_data['non_prized_pending'] = 0;
				$response_data['prized_paid'] = 0;
				$response_data['prized_pending'] = 0;
    			foreach($chit_details as $chit)
    			{	
    				// Non Prized Calculation
    				if($chit->prized_status == 0)
    				{	// bidding
    					if($chit->chit_type != 3)
    					{
	    					$bid = BiddingDetail::selectRaw('*,sum(current_installment_amount) as total_install')->where('group_id',$chit->group_id)->get(); 
	    					$install_amt = isset($bid[0]->total_install) ? ($bid[0]->total_install) : 0;
	    					$non_prized_install = $non_prized_install + $install_amt;
	    					//Receipt
	    					$recpt = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus, sum(penalty) as total_penalty')->where('enrollment_id',$chit->id)->where('group_id',$chit->group_id)->get(); 
	    					$rcpt_amt = isset($recpt[0]->total_receipt) ? ($recpt[0]->total_receipt) : 0;
	    					$bonus_amt = isset($recpt[0]->total_bonus) ? ($recpt[0]->total_bonus) : 0;
	    					$total_received = $rcpt_amt + $bonus_amt;
	    					$non_prized_collected = $non_prized_collected +  $total_received;    					
	    					$non_prized_pending = $non_prized_install - $non_prized_collected;
	    					$response_data['non_prized_paid'] = $non_prized_collected;
	    					$response_data['non_prized_pending'] = $non_prized_pending;    	

    					}
    					if($chit->chit_type == 3)
    					{
    						$bid = CommitmentAuction::selectRaw('*,sum(due_amount) as total_install')->where('enrollment_id',$chit->id)->get(); 
	    					$install_amt = isset($bid[0]->total_install) ? ($bid[0]->total_install) : 0;
	    					$non_prized_install = $non_prized_install + $install_amt;

	    					//Receipt
	    					$recpt = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus, sum(penalty) as total_penalty')->where('enrollment_id',$chit->id)->get(); 
	    					$rcpt_amt = isset($recpt[0]->total_receipt) ? ($recpt[0]->total_receipt) : 0;
	    					$bonus_amt = isset($recpt[0]->total_bonus) ? ($recpt[0]->total_bonus) : 0;
	    					$total_received = $rcpt_amt + $bonus_amt;
	    					$non_prized_collected = $non_prized_collected +  $total_received;    					
	    					$non_prized_pending = $non_prized_install - $non_prized_collected;
	    					$response_data['non_prized_paid'] = $non_prized_collected;
	    					$response_data['non_prized_pending'] = $non_prized_pending;  		
    					}
    				}  
    				// Prized Calculation
    				if($chit->prized_status == 1)
    				{	// bidding
    					if($chit->chit_type != 3)
    					{
	    					$bid1 = BiddingDetail::selectRaw('*,sum(current_installment_amount) as total_install')->where('group_id',$chit->group_id)->get(); 
	    					$install_amt1 = isset($bid1[0]->total_install) ? ($bid1[0]->total_install) : 0;
	    					$prized_install = $prized_install + $install_amt1;

	    					//Receipt
	    					$recpt1 = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus, sum(penalty) as total_penalty')->where('enrollment_id',$chit->id)->where('group_id',$chit->group_id)->get(); 
	    					$rcpt_amt1 = isset($recpt1[0]->total_receipt) ? ($recpt1[0]->total_receipt) : 0;
	    					$bonus_amt1 = isset($recpt1[0]->total_bonus) ? ($recpt1[0]->total_bonus) : 0;
	    					$total_received1 = $rcpt_amt1 + $bonus_amt1;
	    					$prized_collected = $prized_collected +  $total_received1;
	    					$prized_pending = $prized_install - $prized_collected;
	    					$response_data['prized_paid'] = $prized_collected;
	    					$response_data['prized_pending'] = $prized_pending;

    					}
    					if($chit->chit_type ==3)
    					{
    						$bid1 = CommitmentAuction::selectRaw('*,sum(due_amount) as total_install')->where('enrollment_id',$chit->id)->get(); 
	    					$install_amt1 = isset($bid1[0]->total_install) ? ($bid1[0]->total_install) : 0;
	    					$prized_install = $prized_install + $install_amt1;

	    					//Receipt
	    					$recpt1 = Receipt::selectRaw('*,sum(received_amount) as total_receipt, sum(bonus) as total_bonus, sum(penalty) as total_penalty')->where('enrollment_id',$chit->id)->get(); 
	    					$rcpt_amt1 = isset($recpt1[0]->total_receipt) ? ($recpt1[0]->total_receipt) : 0;
	    					$bonus_amt1 = isset($recpt1[0]->total_bonus) ? ($recpt1[0]->total_bonus) : 0;
	    					$total_received1 = $rcpt_amt1 + $bonus_amt1;
	    					$prized_collected = $prized_collected +  $total_received1;
	    					$prized_pending = $prized_install - $prized_collected;
	    					$response_data['prized_paid'] = $prized_collected;
	    					$response_data['prized_pending'] = $prized_pending;
    					}
    				} 
    			} 
	    		$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);
    		}
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    Public function customer_ledger_customer_document_info(Request $request)
    {
    	try
    	{ //customer_id
    		$cond = $request->conditions;
    		$documents = Document::where($cond)->where('collected_from',1)->get();
    		$response_data =[];
    		$total_chit_values = 0;
    		foreach($documents as $doc)
    		{	
    			$doc_det = [];
    			
    			$doc_det['document_id'] = $doc->id;
    			$doc_det['group_info'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->group_det) : []; 
    			$doc_det['scheme_info'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->group_det->scheme_det) : []; 
    			$chit_value = isset($doc->enrollment_det->group_det->scheme_det) ? ($doc->enrollment_det->group_det->scheme_det->chit_value) : []; 
    			$total_chit_values += $chit_value;
    			$doc_det['total_chit_value'] = $total_chit_values;
    			$doc_det['ticket_no'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->ticket_no) : [];  
    			   				
				$doc_details = DocumentDetail::where('document_id',$doc->id)->groupBy('document_type')->where('status',1)->get();    				
				$pan_count = 0;
				$adhar_count = 0;
				$ration_count = 0;
				$pan_document_info ='';
				$adhar_document_info = '';
				$ration_document_info = '';
				$asset_count =0;
				foreach($doc_details as $docts)
				{	    					
    				if($docts->document_type ==1)
    				{
    					$pan_counts = DocumentDetail::where('document_type',1)->where('document_id',$doc->id)->where('status',1)->get();
    					$pan_count = $pan_counts->count();
    					$pan_document_info = (isset($pan_counts[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$pan_counts[0]->document_path) : '';
    				}
    				if($docts->document_type ==2)
    				{
    					$adhar_counts = DocumentDetail::where('document_type',2)->where('document_id',$doc->id)->where('status',1)->get();
						$adhar_count = $adhar_counts->count();
    					$adhar_document_info = (isset($adhar_counts[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$adhar_counts[0]->document_path) : '';
    				}
    				if($docts->document_type ==3)
    				{
    					$ration_counts = DocumentDetail::where('document_type',3)->where('document_id',$doc->id)->where('status',1)->get();
    					$adhar_count = $ration_counts->count();
    					$ration_document_info = (isset($ration_counts[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$ration_counts[0]->document_path) : '';
    				}
    				$doc_det['pan_document_info'] = $pan_document_info;
    				$doc_det['adhar_document_info'] = $adhar_document_info;
    				$doc_det['ration_document_info'] = $ration_document_info;
    				$doc_det['pan_count'] =$pan_count;
    				$doc_det['adhar_count'] =$adhar_count;
    				$doc_det['ration_count'] =$ration_count;
				}
				$asset_det = AssetDetail::where('document_id',$doc->id)->where('status',1)->count(); 
				$asset_count = isset($asset_det) ? ($asset_det) : 0;
				$doc_det['asset_count'] = $asset_count;    

    			array_push($response_data,$doc_det);    		
    		} 
    		if(count($documents)>0)
    		{
    			$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);
    		}
    		else
    		{
    			$response['status'] = 'Error';
	           	$response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

     Public function customer_ledger_guarantor_document_info(Request $request)
    {
    	try
    	{ //customer_id
    		$cond = $request->conditions;
    		$documents = Document::where($cond)->where('collected_from',2)->get();
    		$response_data =[];
    		$total_chit_values = 0;
    		foreach($documents as $doc)
    		{	
    			$doc_det = [];
    			$doc_det['document_id'] = $doc->id;
    			$doc_det['group_info'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->group_det) : []; 
    			$doc_det['scheme_info'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->group_det->scheme_det) : []; 
    			$chit_value = isset($doc->enrollment_det->group_det->scheme_det) ? ($doc->enrollment_det->group_det->scheme_det->chit_value) : []; 
    			$total_chit_values += $chit_value;
    			$doc_det['total_chit_value'] = $total_chit_values;

    			$doc_det['ticket_no'] = isset($doc->enrollment_det) ? ($doc->enrollment_det->ticket_no) : [];  
    			$doc_det['guarantor_info'] = [];  
    			    			
				$doc_det['guarantor_info'] = isset($doc->guarantor_det) ? ($doc->guarantor_det) : [];	
				$doc_details1 = DocumentDetail::where('document_id',$doc->id)->groupBy('document_type')->where('status',1)->get();
				$pan_count1 = 0;
				$adhar_count1 = 0;
				$ration_count1 = 0;
				$pan_document_info1 = '';
				$adhar_document_info1 ='';
				$ration_document_info1 ='';
				$asset_count1 =0;
				foreach($doc_details1 as $docts1)
				{
    				if($docts1->document_type ==1)
    				{
    					$pan_counts1 = DocumentDetail::where('document_type',1)->where('document_id',$doc->id)->where('status',1)->get();
    					$pan_count1 = $pan_counts1->count();
    					$pan_document_info1 = (isset($pan_counts1[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$pan_counts1[0]->document_path) : '';
    				}
    				if($docts1->document_type ==2)
    				{
    					$adhar_counts1 = DocumentDetail::where('document_type',2)->where('document_id',$doc->id)->where('status',1)->get();
    					$adhar_count1 = $adhar_counts1->count();
    					$adhar_document_info1 = (isset($adhar_counts1[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$adhar_counts1[0]->document_path) : '';
    				}
    				if($docts1->document_type ==3)
    				{
    					$ration_counts1 = DocumentDetail::where('document_type',3)->where('document_id',$doc->id)->where('status',1)->get();
    					$ration_count1 = $ration_counts1->count();
    					$ration_document_info1 = (isset($ration_counts1[0]->document_path)) ? env('APP_URL').Storage::url('app/uploads/customer_documents/'.$ration_counts1[0]->document_path) : '';
    				}
    				$doc_det['pan_document_info'] = $pan_document_info1;
    				$doc_det['adhar_document_info'] = $adhar_document_info1;
    				$doc_det['ration_document_info'] = $ration_document_info1;
    				$doc_det['pan_count'] =$pan_count1;
    				$doc_det['adhar_count'] =$adhar_count1;
    				$doc_det['ration_count'] =$ration_count1;    
				}
				$asset_det = AssetDetail::where('document_id',$doc->id)->where('status',1)->count(); 
				$asset_count1 = isset($asset_det) ? ($asset_det) : 0;
				$doc_det['asset_count'] = $asset_count1;
    			
    			array_push($response_data,$doc_det);    		
    		}
    		if(count($documents)>0)
    		{
    			$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);
    		}
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }


    Public function auction_wise_to_be_collected(Request $request)
    {
    	try
    	{	// id
    		$cond = $request->conditions;
    		$chits = ChitDetail::where($cond)->get();
    		if(count($chits)>0)
    		{	
    			$response_data =[];
    			foreach($chits as $cd)
    			{
    				$enrollment_id = $cd->id;
			        $group_id = $cd->group_id;
			        $customer_id = $cd->customer_id;
			        if($cd->chit_type != 3)
			        {
						$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
						$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
						//total_installment_amount
						$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');
						$cd->overall_installment_amount = $total_installment_amount;
						//paid_amount
						$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
						$paid_rec_amount = $receipt[0]->total_paid;
						$bonus_amount = $receipt[0]->bonus_amount;
						$paid_amount = $paid_rec_amount + $bonus_amount;
						$cd->overall_paid_amount = $paid_amount;
						//pending_amount 
						$pending_amount = $total_installment_amount - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;
						//last auction details
						$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
						$current_inst_amt = BiddingDetail::where('id','=',$max_bid_id)->pluck('current_installment_amount');
						//no installment paid 
						$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
					   //auction wise details 
						$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
						$i=0;
						$install_det = [];					
						$total_penalty_amounts = 0;
						$total_bonus_amounts = 0;
						foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
							$cust_det = [];		
							$penalty = 0;					
							$bonus = 0;					
							$inst_amt = $auc_wise_inst_amt->current_installment_amount;
							$auction_id = $auc_wise_inst_amt->id;
							$auc_id  = $auc_wise_inst_amt->id;
							$installment_no  = $auc_wise_inst_amt->installment_no;
							$auc_date_inst  = $auc_wise_inst_amt->auction_date;
		                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();
							$max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
							$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
							if(empty($cus_rec_amt[0])) { 
								$amount_paid = 0;
							} 
							else {
							  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
							  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
							  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
							}
							$ins_amt_wise_rec = $inst_amt - $amount_paid;	
							if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
								$receipt_date = $last_receipt_date_inst[0];
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
								$i++;
							} 
							else {
								$receipt_date = $auc_date_inst;
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
							}
							
							// Bonus days and percentage by heirrachy over all 
							if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
							{
							  	$bonus_days = $cd->customer_det->bonus_days;
							  	$bonus_percentage = $cd->customer_det->customer_bonus;
							}    
							elseif($cd->group_det->group_bonus_days!=0 && $cd->group_det->group_base_bonus!=0)
							{
							  	$bonus_days = $cd->group_det->group_bonus_days;
							  	$bonus_percentage = $cd->group_det->group_base_bonus;
							}
							elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
							{
							  	$bonus_days = $cd->branch_det->bonus_days;
							  	$bonus_percentage = $cd->branch_det->bonus;
							}    
							else
							{
							  	$bonus_days = 0;
							  	$bonus_percentage = 0;
							} 	
							if($pending_days <=  $bonus_days)
							{ 
							  	if($ins_amt_wise_rec==$inst_amt)
							  	{ 
									$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
									$bonus = strval(round($bonus_calculation));
									$cust_det['bonus'] = $bonus;
							  	}
							  	else
							  	{
									$cust_det['bonus'] = 0;
							  	}
							}
							else
							{
							  	$cust_det['bonus'] = 0;
							}
							
							// Penalty Calculation installment wise
			                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
			                {
			                  	$inst_penalty_days = $cd->customer_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
			                }
			                elseif($cd->group_det->group_penalty_days!=0 && $cd->group_det->group_base_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->group_det->group_penalty_days;
			                  	$inst_penalty_percentage = $cd->group_det->group_base_penalty;
			                }
			                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->branch_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
			                }
			                else
			                {
			                  	$inst_penalty_days = "0";
			                  	$inst_penalty_percentage = "0";
			                }
			                $pend_days_int = 0;    
			                $cust_det['penalty_amounts'] = 0;   
			                if($pending_days > $inst_penalty_days)
			                {     	
			                  	$branch=Branch::where('id',$cd->branch_id)->get();
			                  	if($cd->prized_status==0)
			                  	{
			                    	$non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
			                    	$pend_days_int = $pending_days * ($non_prize/ 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	} 
			                  	else
			                  	{               
			                    	$prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
			                    	$pend_days_int = $pending_days * ($prize / 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	}                        		  
			                }    
							$cust_det['installment_no'] = $installment_no;
							$cust_det['auction_date'] = $auc_date_inst;
							$cust_det['inst_amt_wise'] = $inst_amt;
							$cust_det['amount_paid'] = $amount_paid;
							$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
							if($inst_amt == $amount_paid)
							{
								$cust_det['pending_days'] = 0;
							}else
							{
								$cust_det['pending_days'] = $pending_days;
							}
							$cust_det['bonus_inst_wise'] = 0;
							$cust_det['penalty_inst_wise'] = 0;
							$cust_det['discount_inst_wise'] = 0;
							$cust_det['discount_penalty_wise'] = 0;
							$cust_det['auction_id'] = $auction_id;
							$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
							
							array_push($install_det,$cust_det);
							
							$total_penalty_amounts += $penalty;
							$total_bonus_amounts += $bonus;
						};
						$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
						$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
						$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
						$cd->pending_details = $install_det;
							
						array_push($response_data,$cd);			        	
			        }
			        if($cd->chit_type == 3)
			        {			        	
						$slab_types = SlabDue::where('id',$cd->slab_id)->get();   
			            $slab_name = isset($slab_types[0]->slab_name) ? ($slab_types[0]->slab_name) : "";
			            $cd->group_name = "Commitment"."-".$slab_name;
			            $chit_value = isset($slab_types[0]->scheme_det) ? (strval(round($slab_types[0]->scheme_det->chit_value))) : "0"; 

						//total_installment_amount
						$total_installment_amount = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->sum('due_amount');
						$cd->overall_installment_amount = $total_installment_amount;
						//paid_amount
						$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
						$paid_rec_amount = $receipt[0]->total_paid;
						$bonus_amount = $receipt[0]->bonus_amount;
						$paid_amount = $paid_rec_amount + $bonus_amount;
						$cd->overall_paid_amount = $paid_amount;
						//pending_amount 
						$pending_amount = $total_installment_amount - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;
						//last auction details
						$max_bid_id = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->max('id');
						$current_inst_amt = CommitmentAuction::where('id','=',$max_bid_id)->pluck('due_amount');
						//no installment paid 
						$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
					   //auction wise details 
						$auc_wise_inst_amts = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->get();
						$i=0;
						$install_det = [];					
						$total_penalty_amounts = 0;
						$total_bonus_amounts = 0;
						foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
							$cust_det = [];		
							$penalty = 0;					
							$bonus = 0;					
							$inst_amt = $auc_wise_inst_amt->due_amount;
							$auction_id = $auc_wise_inst_amt->id;
							$auc_id  = $auc_wise_inst_amt->id;
							$installment_no  = $auc_wise_inst_amt->installment_no;
							$auc_date_inst  = $auc_wise_inst_amt->auction_date;
		                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();
							$max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
							$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
							if(empty($cus_rec_amt[0])) { 
								$amount_paid = 0;
							} 
							else {
							  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
							  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
							  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
							}
							$ins_amt_wise_rec = $inst_amt - $amount_paid;	
							if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
								$receipt_date = $last_receipt_date_inst[0];
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
								$i++;
							} 
							else {
								$receipt_date = $auc_date_inst;
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
							}
							
							// Bonus days and percentage by heirrachy over all 
							if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
							{
							  	$bonus_days = $cd->customer_det->bonus_days;
							  	$bonus_percentage = $cd->customer_det->customer_bonus;
							}    							
							elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
							{
							  	$bonus_days = $cd->branch_det->bonus_days;
							  	$bonus_percentage = $cd->branch_det->bonus;
							}    
							else
							{
							  	$bonus_days = 0;
							  	$bonus_percentage = 0;
							} 	
							if($pending_days <=  $bonus_days)
							{ 
							  	if($ins_amt_wise_rec==$inst_amt)
							  	{ 
									$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
									$bonus = strval(round($bonus_calculation));
									$cust_det['bonus'] = $bonus;
							  	}
							  	else
							  	{
									$cust_det['bonus'] = 0;
							  	}
							}
							else
							{
							  	$cust_det['bonus'] = 0;
							}
							
							// Penalty Calculation installment wise
			                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
			                {
			                  	$inst_penalty_days = $cd->customer_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
			                }
			                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->branch_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
			                }
			                else
			                {
			                  	$inst_penalty_days = "0";
			                  	$inst_penalty_percentage = "0";
			                }
			                $pend_days_int = 0;    
			                $cust_det['penalty_amounts'] = 0;   
			                if($pending_days > $inst_penalty_days)
			                {     	
			                  	$branch=Branch::where('id',$cd->branch_id)->get();
			                  	if($cd->prized_status==0)
			                  	{
			                    	$non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
			                    	$pend_days_int = $pending_days * ($non_prize/ 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	} 
			                  	else
			                  	{               
			                    	$prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
			                    	$pend_days_int = $pending_days * ($prize / 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	}                        		  
			                }    
							$cust_det['installment_no'] = $installment_no;
							$cust_det['auction_date'] = $auc_date_inst;
							$cust_det['inst_amt_wise'] = $inst_amt;
							$cust_det['amount_paid'] = $amount_paid;
							$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
							if($inst_amt == $amount_paid)
							{
								$cust_det['pending_days'] = 0;
							}else
							{
								$cust_det['pending_days'] = $pending_days;
							}
							$cust_det['bonus_inst_wise'] = 0;
							$cust_det['penalty_inst_wise'] = 0;
							$cust_det['discount_inst_wise'] = 0;
							$cust_det['discount_penalty_wise'] = 0;
							$cust_det['auction_id'] = $auction_id;
							$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
							
							array_push($install_det,$cust_det);
							
							$total_penalty_amounts += $penalty;
							$total_bonus_amounts += $bonus;
						};
						$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
						$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
						$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
						$cd->pending_details = $install_det;
							
						array_push($response_data,$cd);		
			        }
				}
    			$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);
    		}
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = "Error";
    		$response['msg'] = \Lang::get('api.global_error');
    		return response()->json($response,401);
    	}
    }

    public function customer_ledger_relationship_det(Request $request)
    {
        try

        {	
        	$cond = $request->conditions;		
            $relation_ship = Customer::where($cond)->get();
            $response_data = [];
			if(count($relation_ship)>0)
			{ 
				foreach($relation_ship as $relation)
				{ 
					$relation_det = [];
					$relation_det['relationship_info'] = isset($relation->relationship_det) ? ($relation->relationship_det->relationship_name) : [];
					$relation_det['customer_belongs_to'] = isset($relation->customer_belongs_to_det) ? ($relation->customer_belongs_to_det->name) : [];
					array_push($response_data,$relation_det);
				} 			
				$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data;
				return response()->json($response,200);	
			}
			else
			{
				$response['status'] = 'Error';
				$response['msg'] = \Lang::get('api.no_data_found');
				return response()->json($response, 200);
			}
            

        {	//customer_id
        	$cond = $request->conditions;
            $relation_ship = Customer::where($cond)->get();
            $response_data = [];
            foreach($relation_ship as $relation)
            {
                $relation_det = [];
                $relation_det['relationship_info'] = isset($relation->relationship_det) ? ($relation->relationship_det->relationship_name) : [];
                $relation_det['customer_belongs_to'] = isset($relation->customer_belongs_to_det) ? ($relation->customer_belongs_to_det->name) : [];
                array_push($response_data,$relation_det);
            } 
            if(count($relation_ship)>0)
            {
	            $response['status'] = 'Success';
	            $response['msg'] = "";
	            $response['data'] = $response_data;
	            return response()->json($response,200);
            }
            else
            {	
            	$response['status'] = 'Error';
            	$response['msg'] = \Lang::get('api.no_data_found');
            	return response()->json($response, 200);
            }

        }
		}
        catch(\Exception $e)
        {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

    Public function customer_ledger_group_advance_details(Request $request)
    {
    	try
    	{	// enrollment_id
			$cond = $request->conditions;
    		$advance_rcpts = DB::table("advance_receipts")->where('status',1)->where('deleted_at','=',Null)->where($cond)->select("receipt_date","receipt_amount","employee_id","payment_type_id","receipt_no");
    		$receipts =  DB::table("receipts")->where('payment_type_id',7)->where('status',1)->where('deleted_at','=',Null)->where($cond)->select("receipt_date","amount","employee_id","payment_type_id","receipt_no")->union($advance_rcpts)->orderBy('receipt_date','Asc')->get();
    		$response_data=[];
    		$cr_total = 0;
    		$dr_total =0;
    		$balance_avance =0;
			foreach($receipts as $rcpt) 
			{
				$rcpt_det = [];
				$dr =0;
				$cr =0;
				if($rcpt->payment_type_id != 7)
				{
					$rcpt_det['date'] = $rcpt->receipt_date;
					$rcpt_det['receipt_no'] =  $rcpt->receipt_no;
					$rcpt_det['amount'] =  $rcpt->amount;
					$dr_total = $dr_total + $rcpt->amount;
					$dr = $rcpt->amount;
					$rcpt_det['reference_by'] = "";
					$rcpt_det['receipt_amount'] = 0;
					$employee = Employee::where('id',$rcpt->employee_id)->get();
					$rcpt_det['collected_by'] = isset($employee[0]) ? ($employee[0]->first_name." ".$employee[0]->last_name) : "" ;
					$payment_type = PaymentType::where('id',$rcpt->payment_type_id)->get();
					$rcpt_det['retrun_by'] = "";
					$rcpt_det['return_amount'] = 0;
					$rcpt_det['payment_mode'] = isset($payment_type[0]->payment_name) ? ($payment_type[0]->payment_name) : "" ;
				}
				if($rcpt->payment_type_id == 7)
				{	
					$rcpt_det['date'] = $rcpt->receipt_date;
					$rcpt_det['receipt_no'] =  $rcpt->receipt_no;
					$rcpt_det['amount'] = 0;
					$rcpt_det['reference_by'] = "";
					$rcpt_det['receipt_amount'] =  $rcpt->amount;
					$cr_total = $cr_total + $rcpt->amount;
					$cr = $rcpt->amount;
					$rcpt_det['collected_by'] = "";
					$payment_type = PaymentType::where('id',$rcpt->payment_type_id)->get();
					$rcpt_det['retrun_by'] = "";
					$rcpt_det['return_amount'] = 0;
					$rcpt_det['payment_mode'] = isset($payment_type[0]->payment_name) ? ($payment_type[0]->payment_name) : "" ;
				}
				$balance_avance = $balance_avance + $dr - $cr;
				$rcpt_det['balance_avance'] = $balance_avance;
				array_push($response_data,$rcpt_det);
			}   
			if(count($receipts)>0)
			{
	    		$response['status'] = 'Success';
	            $response['msg'] = "";
	            $response['data'] = $response_data;
	            return response()->json($response,200);
			}
			else
			{
				$response['status'] = 'Error';
            	$response['msg'] = \Lang::get('api.no_data_found');
            	return response()->json($response, 200);
			}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    Public function receipt_report_excel_export(Request $request)
    {
        try {  
            $cond = [];
            $start_date = '';
            $end_date = '';
            if(isset($request->tenant_id)){
				$cond['tenant_id'] = $request->tenant_id;
			}
			if(isset($request->branch_id)){
				$cond['branch_id'] = $request->branch_id;
			}
			if(isset($request->group_id)){
				$cond['group_id'] = $request->group_id; 
			}
			if(isset($request->customer_id)){
				$cond['customer_id'] = $request->customer_id; 
			}
			if(isset($request->employee_id)){
				$cond['employee_id'] = $request->employee_id; 
			}
			if(isset($request->payment_type_id)){
				$cond['payment_type_id'] = $request->payment_type_id; 
			} 
			if(isset($request->start_date)) { 
        		$s_date = ($request->start_date['year']).'-'.sprintf('%02d',($request->start_date['month'])).'-'.sprintf('%02d',($request->start_date['day']));
        		$start_date = date('Y-m-d',strtotime($s_date)); 
        	}				
        	if(isset($request->end_date)) {
        		$s_date = ($request->end_date['year']).'-'.sprintf('%02d',($request->end_date['month'])).'-'.sprintf('%02d',($request->end_date['day'])); 
        		$end_date = date('Y-m-d',strtotime($s_date)); 
        	}  
			$receipts = DB::select('call receipt_report(?,?,?,?,?,?,?,?)',array($request->tenant_id,$request->branch_id,$request->group_id,$request->customer_id,$request->employee_id,$request->payment_type_id,$request->start_date,$request->end_date));
			$receiptArray = [];
			$receiptArray[] = ['receipt_date','receipt_no','enrollment_id','customer_id'];
            $i=1;  
            foreach($receipts as $receipt) {
				$receipt_det = [];
				$receipt_det['receipt_date'] = ($receipt->receipt_date!=0) ? date('d/m/Y',strtotime($receipt->receipt_date)) : '';
				$receipt_det['receipt_no'] = ($receipt->receipt_no!=0) ? date('d/m/Y',strtotime($receipt->receipt_no)) : '';
				$receipt_det['enrollment_id'] = isset($receipt->enrollment_id) ? ($receipt->enrollment_id) : "";
				$receipt_det['customer_id'] = isset($receipt->customer_id) ? ($receipt->customer_id) : "";
				$receiptArray[] = $receipt_det;
            } 
            $date = date('d-m-Y'); 
            $time = time();	
            $file_name = $date.'_'.$time.'_receipt_report_excel';
           	Excel::create($file_name, function($excel) use ($receiptArray) {
               $excel->setTitle('Receipt List');
               $excel->sheet('sheet1', function($sheet) use ($receiptArray) {
               		$sheet->fromArray($receiptArray, null, 'A1', false, false);
                    $sheet->cell('A1:AA1', function($cell) {
                        // Set font
                        $cell->setFont(array(
                            'family'     => 'Calibri',
                            'size'       => '12',
                            'bold'       =>  true
                        ));
                    });
                });
            })->store('xlsx',storage_path('app/uploads/receipt_reports'));
            $filepath = env('APP_URL').Storage::url('app/uploads/receipt_reports/').$file_name.'.xlsx';
            if(count($receipts)>0)
            {
	            $response['status'] = 'Success';
	            $response['filepath'] = $filepath;
	            return response()->json($response,200);
            }
            else
            {
            	$response['status'] = 'Error';
				$response['msg'] = \Lang::get('api.no_data_found');
		        return response()->json($response, 200);
            }
        }
        catch(\Exception $e) {
			$response['status'] = 'Error';
			$response['msg'] = \Lang::get('api.global_error');
	        return response()->json($response, 401);
        }
    }

    public function advance_receipt_cheque_return(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'cheque_clear_return_date' => 'required',
            'cheque_return_amount' => 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
                $receipt = AdvanceReceipt::find($request->id);
                if(isset($request->cheque_clear_return_date)) {
                    $ccr_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
                    $receipt->cheque_clear_return_date = date('Y-m-d',strtotime($ccr_date));
                }
                else {
                    $receipt->cheque_clear_return_date = '0000-00-00';
                } 
                $receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
                $receipt->cheque_return_amount = isset($request->cheque_return_amount) ? ($request->cheque_return_amount) : 0;
                $receipt->status = isset($request->status) ? ($request->status) : 3;
                if($receipt->save()) {
                    $response['status'] = 'Success';
                    $response['msg'] = \Lang::get('api.success_cheque_returned');
                    $response['data'] = $receipt;
                    return response()->json($response,200);
                }
                else {
                    $response['status']="Error";
                    $response['msg']=\Lang::get('api.global_error');
                    return response()->json($response,401);
                }
            }
            catch(\Exception $e) {
                $response['status'] = 'Error';
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }

    public function advance_receipt_cheque_cleared(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'cheque_clear_return_date' => 'required',
            'cheque_debit_to' => 'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
                $receipt = AdvanceReceipt::find($request->id);
                if(isset($request->cheque_clear_return_date)) {
                    $ccr_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
                    $receipt->cheque_clear_return_date = date('Y-m-d',strtotime($ccr_date));
                }
                else {
                    $receipt->cheque_clear_return_date = '0000-00-00';
                }
                $receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
                $receipt->cheque_debit_to = isset($request->cheque_debit_to) ? ($request->cheque_debit_to) : 0; 
                $receipt->status = isset($request->status) ? ($request->status) : 1;
                if($receipt->save()) {

                    $response['status'] = 'Success';
                    $response['msg'] = \Lang::get('api.success_cheque_cleared');
                    $response['data'] = $receipt;
                    return response()->json($response,200);
                }
                else {
                    $response['status']="Error";
                    $response['msg']=\Lang::get('api.global_error');
                    return response()->json($response,401);
                }
            }
            catch(\Exception $e) {
                $response['status'] = 'Error';
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }

    Public function customer_ledger_get_enrol_details(Request $request)
    {
    	try
    	{  // id
    		$cond  = $request->conditions;
    		$chits =  ChitDetail::where($cond)->get();
    		$response_data =[];
    		$response_data['group_name'] ='';
    		$response_data['ticket_no'] = '';
    		foreach($chits as $chit)
    		{
	    		if($chit->chit_type != 3)
	    		{
					$response_data['group_name'] =  isset($chit->group_det) ? ($chit->group_det->group_name) : "";
					$response_data['ticket_no'] = isset($chit->ticket_no) ? ($chit->ticket_no) : "";
	    		}
	    		if($chit->chit_type == 3)
	    		{
	    			$slab_types = SlabDue::where('id',$chit->slab_id)->get();   
		            $slab_name = isset($slab_types[0]->slab_name) ? ($slab_types[0]->slab_name) : "";
		            $response_data['group_name'] = "Commitment"."-".$slab_name;
		            $response_data['ticket_no'] = "-";
	    		}    			
    		}
			$response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $response_data;
            return response()->json($response,200);    	
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }
    

    public function get_enrol_details_duplicate(Request $request) {
        try {
            //enrollment id
            $cond = $request->conditions; 
            $chit_details = ChitDetail::where($cond)->orderBy('id','desc')->get();
			
			$response_data = [];
			
			foreach($chit_details as $cd) {
				$enrollment_id = $cd->id;
		        $group_id = $cd->group_id;
		        $cd->group_info = isset($cd->group_det) ? ($cd->group_det) : [];
		        $customer_id = $cd->customer_id;
				$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
				$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
				//total_installment_amount
				$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');
				$cd->overall_installment_amount = $total_installment_amount;
				//paid_amount
				$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
				$paid_rec_amount = $receipt[0]->total_paid;
				$bonus_amount = $receipt[0]->bonus_amount;
				$paid_amount = $paid_rec_amount + $bonus_amount;
				$cd->overall_paid_amount = $paid_amount;
				//pending_amount 
				$pending_amount = $total_installment_amount - $paid_amount;
				$cd->overall_pending_amount = $pending_amount;
				$cd->chit_value = $chit_value;
				//last auction details
				$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
				$current_inst_amt = BiddingDetail::where('Id','=',$max_bid_id)->pluck('current_installment_amount');
				//no installment paid 
				$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
			   //auction wise details 
				$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
				$i=0;
				$install_det = [];					
				$total_penalty_amounts = 0;
				$total_bonus_amounts = 0;
				foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
					$cust_det = [];		
					$penalty = 0;					
					$bonus = 0;					
					$inst_amt = $auc_wise_inst_amt->current_installment_amount;
					$auction_id = $auc_wise_inst_amt->id;
					$auc_id  = $auc_wise_inst_amt->id;
					$installment_no  = $auc_wise_inst_amt->installment_no;
					$auc_date_inst  = $auc_wise_inst_amt->auction_date;
                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();	
					 $max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
					 $last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
					if(empty($cus_rec_amt[0])) { 
						$amount_paid = 0;
					} 
					else {
					  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
					  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
					  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
					}
					$ins_amt_wise_rec = $inst_amt - $amount_paid;
					if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
						$receipt_date = $last_receipt_date_inst[0];
						$current_date = date('Y-m-d');
						$datetime1 = new DateTime($receipt_date);
						$datetime2 = new DateTime($current_date);
						$interval = $datetime1->diff($datetime2);
						$pending_days = $interval->format('%a');
						$i++;
					} 
					else {
						$receipt_date = $auc_date_inst;
						$current_date = date('Y-m-d');
						$datetime1 = new DateTime($receipt_date);
						$datetime2 = new DateTime($current_date);
						$interval = $datetime1->diff($datetime2);
						$pending_days = $interval->format('%a');
					}
					
					// Bonus days and percentage by heirrachy over all 
					if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
					{
					  $bonus_days = $cd->customer_det->bonus_days;
					  $bonus_percentage = $cd->customer_det->customer_bonus;
					}    
					elseif($cd->group_det->group_bonus_days!=0 && $cd->group_det->group_base_bonus!=0)
					{
					  $bonus_days = $cd->group_det->group_bonus_days;
					  $bonus_percentage = $cd->group_det->group_base_bonus;
					}
					elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
					{
					  $bonus_days = $cd->branch_det->bonus_days;
					  $bonus_percentage = $cd->branch_det->bonus;              
					}    
					else
					{
					  $bonus_days = 0;
					  $bonus_percentage = 0;
					} 			
				
					
					if($pending_days <=  $bonus_days)
					{ 
					  if($ins_amt_wise_rec==$inst_amt)
					  { 
						$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
						$bonus = strval(round($bonus_calculation));
						$cust_det['bonus'] = $bonus;
					  }
					  else
					  {
						$cust_det['bonus'] = 0;
					  }
					}
					else
					{
					  $cust_det['bonus'] = 0;
					}
					
				// Penalty Calculation installment wise
	                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
	                {
	                  $inst_penalty_days = $cd->customer_det->penalty_days;
	                  $inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
	                }
	                elseif($cd->group_det->group_penalty_days!=0 && $cd->group_det->group_base_penalty!=0)
	                {
	                  $inst_penalty_days = $cd->group_det->group_penalty_days;
	                  $inst_penalty_percentage = $cd->group_det->group_base_penalty;                  
	                }
	                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
	                {
	                  $inst_penalty_days = $cd->branch_det->penalty_days;
	                  $inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
	                }
	                else
	                {
	                  $inst_penalty_days = "0";
	                  $inst_penalty_percentage = "0";
	                }
	                $cust_det['penalty_percentage'] = strval($inst_penalty_percentage);
	                $pend_days_int = 0;    
	                $cust_det['penalty_amounts'] = 0;   
					
	                if($pending_days > $inst_penalty_days)
	                {     		
	                  $branch=Branch::where('id',$cd->branch_id)->get();
	                  if($cd->prized_status==0)
	                  {
	                    $non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
	                    $pend_days_int = $pending_days * ($non_prize/ 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  } 
	                  else
	                  {               
	                    $prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
	                    $pend_days_int = $pending_days * ($prize / 100);
	                    $current_month_days = date('t'); 
	                    $total_days_int = $pend_days_int / $current_month_days;
	                    $penalty = $total_days_int * $ins_amt_wise_rec;
	                    $cust_det['penalty_amounts'] = strval(round($penalty));
	                  }                        		  
	                }    
					
					$cust_det['installment_no'] = $installment_no;
					$cust_det['inst_amt_wise'] = $inst_amt;
					$cust_det['amount_paid'] = $amount_paid;
					$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
					$cust_det['pending_days'] = $pending_days;
					$cust_det['bonus_inst_wise'] = 0;
					$cust_det['penalty_inst_wise'] = 0;
					$cust_det['discount_inst_wise'] = 0;
					$cust_det['discount_penalty_wise'] = 0;
					$cust_det['auction_id'] = $auction_id;
					$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
					$cust_det['cancel_dividend_amount'] =0;
					if($ins_amt_wise_rec>0) {
						array_push($install_det,$cust_det);
					}	
					$total_penalty_amounts += $penalty;
					$total_bonus_amounts += $bonus;
				};
				$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
				$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
				$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
				$cd->pending_details = $install_det;
				$cd->scheme_info = [];
				if(isset($cd->group_det)) {
					$cd->scheme_info = isset($cd->group_det->scheme_det) ? ($cd->group_det->scheme_det) : [];
				}
				if($cd) {
					array_push($response_data,$cd);
				}
			}
			$response['status'] = 'Success';
			$response['msg'] = "";
			$response['data'] = $response_data;
			return response()->json($response,200);
		}
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

    public function store_receipt_duplicate(Request $request)
    {	
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required',
            'branch_id' => 'required',
            'customer_id' => 'required',
            //'group_id' => 'required',
			'employee_id' => 'required',
			'enrollment_id'=> 'required',
			//'receipt_type'=>'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try { 
				if(isset($request->Installments)) 
				{  

					$count = count($request->Installments);
					$receipt_no = '';
					for($i=0; $i<$count; $i++)
					{	
						$receipt = new Receipt(); 
						$receipt->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
						$receipt->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
						$receipt->other_branch = isset($request->other_branch) ? ($request->other_branch) : 0;
						$receipt->customer_id = isset($request->customer_id) ? ($request->customer_id) : 0;
						$receipt->enrollment_id = isset($request->enrollment_id) ? ($request->enrollment_id) : 0;
						$receipt->group_id = isset($request->group_id) ? ($request->group_id) : 0;
						$receipt->ticket_no = isset($request->ticket_no) ? ($request->ticket_no) : '';
						$receipt->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0;
						$employee_branch_id = Employee::where('id','=',$receipt->employee_id)->pluck('branch_id');
						$receipt->employee_branch_id = $employee_branch_id;
						$receipt->adjust_id = isset($request->adjust_id) ? ($request->adjust_id) : 0;
						$receipt->amount = isset($request->amount) ? ($request->amount) : 0;
						if(isset($request->receipt_date)) {
							$r_date = ($request->receipt_date['year']).'-'.sprintf('%02d',($request->receipt_date['month'])).'-'.sprintf('%02d',($request->receipt_date['day'])); 
							$receipt->receipt_date = date('Y-m-d',strtotime($r_date)); 
						}
						else {
							$receipt->receipt_date = '0000-00-00';
						}
						//$receipt->receipt_date = isset($request->receipt_date) ? ($request->receipt_date) : "0000-00-00";
						$receipt->type_of_collection = 'receipt';
						if($request->receipt_type == 'Advanced Receipt') {
							$receipt->payment_type_id = 7;
							$receipt->type_of_collection = 'advance_receipt';
						} else {
						$receipt->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;
						$receipt->type_of_collection = 'receipt';
						}
						$receipt->debit_to	 = isset($request->debit_to	) ? ($request->debit_to	) : '';
						$receipt->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : '';
						if(isset($request->cheque_date)) {
							$c_date = ($request->cheque_date['year']).'-'.sprintf('%02d',($request->cheque_date['month'])).'-'.sprintf('%02d',($request->cheque_date['day'])); 
							$receipt->cheque_date = date('Y-m-d',strtotime($c_date)); 
						}
						else {
							$receipt->cheque_date = '0000-00-00';
						}
						//$receipt->cheque_date = isset($request->cheque_date) ? ($request->cheque_date) : "0000-00-00";
						if(isset($request->cheque_clear_return_date)) {
							$cc_date = ($request->cheque_clear_return_date['year']).'-'.sprintf('%02d',($request->cheque_clear_return_date['month'])).'-'.sprintf('%02d',($request->cheque_clear_return_date['day'])); 
							$receipt->cheque_clear_return_date = date('Y-m-d',strtotime($cc_date)); 
						}
						else {
							$receipt->cheque_clear_return_date = '0000-00-00';
						}
						//$receipt->cheque_clear_return_date = isset($request->cheque_clear_return_date) ? ($request->cheque_clear_return_date) : "0000-00-00";
						$receipt->bank_name_id = isset($request->bank_name_id) ? ($request->bank_name_id) : 0;
						$receipt->branch_name = isset($request->branch_name) ? ($request->branch_name) : '';
						$receipt->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0;
						if(isset($request->transaction_date)) {
							$t_date = ($request->transaction_date['year']).'-'.sprintf('%02d',($request->transaction_date['month'])).'-'.sprintf('%02d',($request->transaction_date['day'])); 
							$receipt->transaction_date = date('Y-m-d',strtotime($t_date)); 
						}
						else {
							$receipt->transaction_date = '0000-00-00';
						}
						//$receipt->transaction_date = isset($request->transaction_date) ? ($request->transaction_date) : "0000-00-00";
						$receipt->transaction_bill = isset($request->transaction_bill) ? ($request->transaction_bill) : '';
						$receipt->transaction_book = isset($request->transaction_book) ? ($request->transaction_book) : '';
						$receipt->neft_adjust_id = isset($request->neft_adjust_id) ? ($request->neft_adjust_id) : 0;
						$receipt->receipt_mode = 'ERP';
						$receipt->receipt_time = isset($request->receipt_time) ? ($request->receipt_time) : '00:00:00';
						if(isset($request->accounts_date)) {
							$a_date = ($request->accounts_date['year']).'-'.sprintf('%02d',($request->accounts_date['month'])).'-'.sprintf('%02d',($request->accounts_date['day'])); 
							$receipt->accounts_date = date('Y-m-d',strtotime($a_date)); 
						}
						else {
							$receipt->accounts_date = date('Y-m-d');
						}
						//$receipt->accounts_date = isset($request->accounts_date) ? ($request->accounts_date) : "0000-00-00";
						$receipt->printed = isset($request->printed) ? ($request->printed) : 0;
						$receipt->auction_id = isset($request->Installments[$i]['auction_id']) ? $request->Installments[$i]['auction_id'] : '';
						$receipt->installment_no = isset($request->Installments[$i]['installment_no']) ? $request->Installments[$i]['installment_no'] : '';
						$receipt->pending_days = isset($request->Installments[$i]['pending_days']) ? $request->Installments[$i]['pending_days'] : '';
						$receipt->penalty = isset($request->Installments[$i]['penalty_inst_wise']) ? $request->Installments[$i]['penalty_inst_wise'] : '';
						$receipt->bonus = isset($request->Installments[$i]['bonus_inst_wise']) ? $request->Installments[$i]['bonus_inst_wise'] : '';
						$receipt->discount = isset($request->Installments[$i]['discount_inst_wise']) ? $request->Installments[$i]['discount_inst_wise'] : '';
						$receipt->received_amount = isset($request->Installments[$i]['received_amount']) ? $request->Installments[$i]['received_amount'] : '';
						$receipt->cancel_dividend_amount = isset($request->Installments[$i]['cancel_dividend_inst_wise']) ? $request->Installments[$i]['cancel_dividend_inst_wise'] : '';						
						$receipt->remarks = isset($request->remarks) ? ($request->remarks) : '';
						$receipt->created_by = isset($request->created_by) ? ($request->created_by) : 0;
						$receipt->status = isset($request->status) ? ($request->status) : 1;
						if($request->payment_type_id == 2){
							$receipt->status = isset($request->status) ? ($request->status) : 2;
						}
						if($receipt->penalty !=0 || $receipt->received_amount !=0) {
							if($receipt->save()) {
								if($receipt_no=='') {
									//sleep(10);
									$receipt_no = 'RCPT-'.sprintf('%05d', $receipt->id);
								}			
								$receipt->receipt_no = $receipt_no;
								$receipt->save();	
		                	}
	                		else {
			                    $response['status']="Error";
			                    $response['msg']=\Lang::get('api.global_error');
			                    return response()->json($response,401);
		                	}
		            	}
		            }
            		$response['status'] = 'Success';
			        $response['msg'] = \Lang::get('api.success_receipt_added');
			        return response()->json($response,200);	
	            }				
				else {
					$response['status'] = 'Error';
					$response['msg'] = \Lang::get('api.no_data_found');
					return response()->json($response, 401);
				}
			}
			catch(\Exception $e) {
				$response['status'] = 'Error';
				$response['msg'] = \Lang::get('api.global_error');
				return response()->json($response, 401);
			}
		}
	}
	
	 public function receipt_print(Request $request) {
        try {
            $cond = $request->conditions; 
            $receipts = Receipt::selectRaw('*, sum(received_amount) as total_paid, sum(bonus) as bonus_amount, sum(penalty) as penalty_amount')->where($cond)->orderBy('id','desc')->get();
			
			foreach($receipts as $receipt) {
				$receipt->tenant_info = isset($receipt->tenant_det) ? ($receipt->tenant_det) : [];                    
                $receipt->branch_info = isset($receipt->branch_det) ? ($receipt->branch_det) : [];
                $receipt->customer_info = isset($receipt->customer_det) ? ($receipt->customer_det) : []; 
                $receipt->group_info = isset($receipt->group_det) ? ($receipt->group_det) : [];
                $receipt->enrollment_info = isset($receipt->enrollment_det) ? ($receipt->enrollment_det) : [];	
                $receipt->employee_info = isset($receipt->employee_det) ? ($receipt->employee_det) : [];
				$receipt->payment_type_info = isset($receipt->payment_type_det) ? ($receipt->payment_type_det) : [] ;				
			}
            $response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $receipts;
            return response()->json($response,200);
        }
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }

    public function commitment_receipt_report(Request $request) {
        try {  
            $cond = [];
            $cond1 = [];
            $start_date = null;
            $end_date = null;
            if(isset($request->tenant_id)){
				$cond['tenant_id'] = $request->tenant_id;
			}						
			if(isset($request->customer_id)){
				$cond['customer_id'] = $request->customer_id; 
			}
			if(isset($request->employee_id)){
				$cond['employee_id'] = $request->employee_id; 
			}
			if(isset($request->payment_type_id)){
				$cond['payment_type_id'] = $request->payment_type_id; 
			} 
			if(isset($request->start_date)) { 
        		$s_date = ($request->start_date['year']).'-'.sprintf('%02d',($request->start_date['month'])).'-'.sprintf('%02d',($request->start_date['day']));
        		$start_date = date('Y-m-d',strtotime($s_date)); 
        	}				
        	if(isset($request->end_date)) {
        		$s_date = ($request->end_date['year']).'-'.sprintf('%02d',($request->end_date['month'])).'-'.sprintf('%02d',($request->end_date['day'])); 
        		$end_date = date('Y-m-d',strtotime($s_date)); 
        	}  
          
			$response["start_date"] = $start_date;
			$response["end_date"] = $end_date;
			if($request->branch_id >0){
				$cond1['branch_id'] = $request->branch_id;
				$receipts = DB::select('call commitment_receipt_report(?,?,?,?,?,?,?)',array($request->tenant_id,$request->branch_id,$request->customer_id,$request->employee_id,$request->payment_type_id,$start_date,$end_date));
			}
			if($request->branch_id == 0)
			{
				$receipts = DB::select('call commitment_receipt_report(?,?,?,?,?,?,?)',array($request->tenant_id,$request->branch_id,$request->customer_id,$request->employee_id,$request->payment_type_id,$start_date,$end_date));
			}

            $i=1;  
            $grand_received = 0;
            $tot_received = 0;
            $grand_paid_amt = 0;
            $tot_paid_amt = 0;
            $grand_penalty_amt = 0;
            $tot_penalty_amt = 0;
            $grand_bonus_amt = 0;
            $tot_bonus_amt = 0;
            foreach($receipts as $receipt) {
                $receipt->sno = $i;
				$receipt->tenant_info = isset($receipt->tenant_det) ? ($receipt->tenant_det) : [];
				$receipt->branch_name = isset($receipt->branchName) ? ($receipt->branchName) : '-';	
				$receipt->slabName = isset($receipt->slab_name) ? ($receipt->slab_name) : '-';								
				$receipt->employee_name = isset($receipt->first_name) ? ($receipt->first_name) : '-';
				$receipt->customer_name = isset($receipt->name) ? ($receipt->name) : '';
				$receipt->enrollment_branch_name = isset($receipt->enroll_branch) ? ($receipt->enroll_branch) : [];	
				$receipt->payment_type_name = '-';
				if(isset($receipt->payment_type_id)) {
					$payment_type_det = PaymentType::find($receipt->payment_type_id);	
					$receipt->payment_type_name = isset($payment_type_det) ? ($payment_type_det->payment_name) : '-';
				}
				$receipt->bank_name = '-';
				$receipt->bank_branch_name = '-';
				if(isset($receipt->bank_name_id)) {
					$bank_det = BankDetail::find($receipt->bank_name_id);	
					$receipt->bank_name = isset($bank_det) ? ($bank_det->bank_name) : '-';
					$receipt->bank_branch_name = isset($bank_det) ? ($bank_det->bank_branch_name) : '-';
				}						
				$total_paid = $receipt->total_paid;
				$bonus_amount = $receipt->bonus_amount;
				$penalty_amount = $receipt->penalty_amount;
				$total_received_amount = $receipt->total_paid + $receipt->penalty_amount;
				$receipt->other_branch_info = [];
				$receipt->other_branch_name = '-';
				if(($receipt->other_branch!=0) && ($receipt->enrollment_id)) {
					$other_branch_det = ChitDetail::find($receipt->enrollment_id);
					$receipt->other_branch_info = isset($other_branch_det->branch_det) ? ($other_branch_det->branch_det) : [];
					$receipt->other_branch_name = isset($other_branch_det->branch_det) ? ($other_branch_det->branch_det->branch_name) : '-';
				}
				$receipt->receipt_date = ($receipt->receipt_date!=0) ? date('d/m/Y',strtotime($receipt->receipt_date)) : '0000-00-00';
				$receipt->accounts_date = ($receipt->accounts_date!=0) ? date('d/m/Y',strtotime($receipt->accounts_date)) : '0000-00-00';
				$receipt->cheque_date = ($receipt->cheque_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_date)) : '0000-00-00';
				$receipt->cheque_clear_return_date = ($receipt->cheque_clear_return_date!=0) ? date('d/m/Y',strtotime($receipt->cheque_clear_return_date)) : '0000-00-00';
				$receipt->cheque_bank_name = '-';
				if(isset($receipt->cheque_debit_to)) {
					$cheque_bank_det = BankDetail::find($receipt->cheque_debit_to);	
					$receipt->cheque_bank_name = isset($cheque_bank_det) ? ($cheque_bank_det->bank_name) : '-';					
				}				
				if($receipt->status == 1 )
				{
					$receipt->status_name = "Active";
				}	
				else if($receipt->status == 2 )
				{
					$receipt->status_name = "Pending";
				}		
				else if($receipt->status == 3 )
				{
					$receipt->status_name = "Return";
				}	
				else if($receipt->status == 0 )
				{
					$receipt->status_name = "In-Active";
				}else
				{
					$receipt->status_name = "-";
				}
				$receipt->total_received_amount = $total_received_amount;
				$receipt->total_paid = $total_paid;
				$receipt->penalty_amount = $penalty_amount;
				$receipt->bonus_amount = $bonus_amount;
                $tot_received += $total_received_amount;
                $tot_paid_amt += $total_paid;
                $tot_penalty_amt += $penalty_amount;
                $tot_bonus_amt += $bonus_amount;
                $i++;
            }
            $grand_received +=$tot_received;
            $grand_paid_amt +=$tot_paid_amt;
            $grand_penalty_amt +=$tot_penalty_amt;
            $grand_bonus_amt +=$tot_bonus_amt;

            $grand_received_format =0;
            $grand_paid_amt_format =0;
            $grand_penalty_amt_format =0;
            $grand_bonus_amt_format =0;

            if($grand_received > 0)
            {   
                $num = $grand_received;
                $grand_received_format = $this->moneyFormatIndia($num);  
            } 
            if($grand_paid_amt > 0)
            {   
                $num = $grand_paid_amt;
                $grand_paid_amt_format = $this->moneyFormatIndia($num);  
            } 
            if($grand_penalty_amt > 0)
            {   
                $num = $grand_penalty_amt;
                $grand_penalty_amt_format = $this->moneyFormatIndia($num);  
            } 
            if($grand_bonus_amt > 0)
            {   
                $num = $grand_bonus_amt;
                $grand_bonus_amt_format = $this->moneyFormatIndia($num);  
            } 
            $response['grand_received'] = $grand_received_format;
            $response['grand_paid_amt'] = $grand_paid_amt_format;
            $response['grand_penalty_amt'] = $grand_penalty_amt_format;
            $response['grand_bonus_amt'] = $grand_bonus_amt_format;
            $response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $receipts;
            return response()->json($response,200);
        }
        catch(\Exception $e) {
		$response['status'] = 'Error';
		$response['msg'] = \Lang::get('api.global_error');
        return response()->json($response, 401);
        }
    }
	

	Public function module_name_list(Request $request)
    {
    	try
    	{
    		$module_tables = LogMonitoringTable::orderBy('id','Asc')->get();
    		$i = 1;
    		foreach($module_tables as $module)
    		{
    			$module->sno = $i;
    			$i++;
    		}    		
    		$response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $module_tables;
            return response()->json($response,200);
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
    	}
    }

    
    Public function log_monitoring_report(Request $request)
    {
    	try
    	{
    		$cond = [];
    		$module_name ='';
    		if(isset($request->branch_id))
    		{
    			$cond['branch_id'] = $request->branch_id;
    		}
    		if(isset($request->module_name))
    		{
    			$module_id = $request->module_name;
    		
	    		if($module_id == 1 )
	    		{
	    			$module_name = 'Receipt';
	    			$module_table = Receipt::where($cond)->where('updated_at','!=','')->withTrashed()->get();
	    		}
	    	
	    		if($module_id == 2)
	    		{
	    			$module_name = 'Customer';
	    			$module_table = Customer::where($cond)->where('updated_at','!=','')->withTrashed()->get();  
	    		}
	    		if($module_id == 3)
	    		{
	    			$module_name = 'Customer Enrollment';
	    			$module_table = ChitDetail::where($cond)->where('chit_type','!=',3)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 4)
	    		{
	    			$module_name = 'Auction Details';
	    			$module_table = BiddingDetail::where($cond)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 5)
	    		{
	    			$module_name = 'Commitment Chit';
	    			$module_table = ChitDetail::where($cond)->withTrashed()->where('chit_type','=',3)->where('updated_at','!=','')->get();    			
	    		}
	    		if($module_id == 6)
	    		{
	    			$module_name = 'Commitment Payment Details';
	    			$module_table = Payment::where($cond)->where('payment_for','=',7)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 7)
	    		{
	    			$module_name = 'Commission Payment';
	    			$module_table = AgentCommissionPayment::where($cond)->where('updated_at','!=','')->withTrashed()->get(); 
	    		}
	    		if($module_id == 8)
	    		{
	    			$module_name = 'Leads';
	    			$module_table = LeadManagement::where($cond)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 9)
	    		{
	    			$module_name = 'Enroll Amount Refund';
	    			$module_table = Payment::where($cond)->where('payment_for','=',3)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 10)
	    		{
	    			$module_name = 'Commitment Receipt';
	    			$module_table = Receipt::where($cond)->where('group_id','=',0)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 11)
	    		{
	    			$module_name = 'Advance Receipts';
	    			$module_table = AdvanceReceipt::where($cond)->where('updated_at','!=','')->withTrashed()->get();    			
	    		}
	    		if($module_id == 12)
	    		{
	    			$module_name = 'Bid Payment Adjustment Receipts';
	    			$module_table = Receipt::where($cond)->where('type_of_collection','=','b.p.adj.receipt')->where('updated_at','!=','')->withTrashed()->get(); 
	    		}
	    		if($module_id == 13)
	    		{
	    			$module_name = 'Other Charges Receipts';
	    			$module_table = OtherCharge::where($cond)->where('updated_at','!=','')->withTrashed()->get();
	    		}
    		}
    		$i = 1;
    		$response_data = [];
    		foreach($module_table as $module)
    		{
    			$log_det = [];
    			$update_by = isset($module->updated_by) ? ($module->updated_by) : '';
    			$deleted_by = isset($module->deleted_by) ? ($module->deleted_by) : '';
    			if($update_by != 0 || $deleted_by !=0)
    			{
	    			$log_det['sno'] = $i;
	    			$log_det['branch_name'] = isset($module->branch_det) ? ($module->branch_det->branch_name) : '';
	    			$log_det['module_name'] = $module_name;
	    			$log_det['updated_at'] =  isset($module->updated_at) ? (date('Y-m-d', strtotime($module->updated_at))) : "0000-00-00";
	    			// updated By
	    			$update_by = isset($module->updated_by) ? ($module->updated_by) : '';
	    			$update_user = User::where('id',$update_by)->get();
	    			$emp_id = isset($update_user[0]->employee_id) ? ($update_user[0]->employee_id) : 0;
	    			$update_employee = Employee::where('id',$emp_id)->get(); 
	    			$update_emp_full_name = isset($update_employee[0]->first_name) ? ($update_employee[0]->first_name .' '.$update_employee[0]->last_name) : '';    			
	    			$log_det['updated_by'] = $update_emp_full_name;
	    			$log_det['deleted_at'] =  isset($module->deleted_at) ? (date('Y-m-d', strtotime($module->deleted_at))) : "0000-00-00";
	    			
	    			// deleted By
	    			$deleted_by = isset($module->deleted_by) ? ($module->deleted_by) : '';
	    			$deleted_user = User::where('id',$deleted_by)->get();
	    			$emp_id1 = isset($deleted_user[0]->employee_id) ? ($deleted_user[0]->employee_id) : 0;
	    			$delete_employee = Employee::where('id',$emp_id1)->get();
	    			$delete_emp_full_name = isset($delete_employee[0]->first_name) ? ($delete_employee[0]->first_name .' '.$delete_employee[0]->last_name) : '';
	    			$log_det['deleted_by'] = $delete_emp_full_name;
	    			$log_det['deletion_remark'] = isset($module->deletion_remark) ? ($module->deletion_remark) : '';
	    			$i++;
	    			array_push($response_data,$log_det);
    			}
    		}
    		$response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $response_data;
            return response()->json($response,200);
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            $response['msg'] =$e;
            return response()->json($response, 401);
    	}
    }

     public function moneyFormatIndia($num)
    {       
        $nums = explode(".",$num);
        if(count($nums)>2){
            return "0";
        }
        else{
            if(count($nums)==1){
                $nums[1]="00";
            } 
            $num = $nums[0];
            $explrestunits = "" ;
            if(strlen($num)>3){
                $lastthree = substr($num, strlen($num)-3, strlen($num));
                $restunits = substr($num, 0, strlen($num)-3); 
                $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; 
                $expunit = str_split($restunits, 2);
                for($i=0; $i<sizeof($expunit); $i++){

                    if($i==0)
                    {
                        $explrestunits .= (int)$expunit[$i].","; 
                    }else{
                        $explrestunits .= $expunit[$i].",";
                    }
                }
                $thecash = $explrestunits.$lastthree; 
                return $thecash;
            } else {
                $thecash = $num;
                return $thecash;
            }  
        }
    }    

    public function store_unknown_transaction(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'tenant_id'=>'required',
            'branch_id'=>'required',
        ]); 
        if($validator->fails()){
            $response['status']="Error";
            $response['msg']=$validator->error()->first();
            return response()->json($response,401);
        }
        else {
            try { 
                $unknown_trans=new UnknownTransaction(); 
                $unknown_trans->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
                $unknown_trans->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
                if(isset($request->received_date)) {
                    $s_date = ($request->received_date['year']).'-'.sprintf('%02d',($request->received_date['month'])).'-'.sprintf('%02d',($request->received_date['day'])); 
                    $unknown_trans->received_date = date('Y-m-d',strtotime($s_date)); 
                }
                else {
                        $unknown_trans->received_date = '0000-00-00';
                } 
                $unknown_trans->amount_received = isset($request->amount_received) ? ($request->amount_received) : 0 ;
                $unknown_trans->employee_id = isset($request->employee_id) ? ($request->employee_id) : 0 ;
                $unknown_trans->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;
                $unknown_trans->debit_to = isset($request->debit_to) ? ($request->debit_to) : 0 ;
                $unknown_trans->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : 0 ;
				if(isset($request->cheque_date)) {
					$f_date = ($request->cheque_date['year']).'-'.sprintf('%02d',($request->cheque_date['month'])).'-'.sprintf('%02d',($request->cheque_date['day'])); 
					$unknown_trans->cheque_date  = date('Y-m-d',strtotime($f_date)); 
				}
				else {
					$unknown_trans->cheque_date  = '0000-00-00';
				}
                $unknown_trans->bank_id = isset($request->bank_id) ? ($request->bank_id) : 0 ;
                $unknown_trans->bank_branch_name = isset($request->bank_branch_name) ? ($request->bank_branch_name) : 0 ;
				if(isset($request->transaction_date)) {
					$f_date = ($request->transaction_date['year']).'-'.sprintf('%02d',($request->transaction_date['month'])).'-'.sprintf('%02d',($request->transaction_date['day'])); 
					$unknown_trans->transaction_date  = date('Y-m-d',strtotime($f_date)); 
				}
				else {
					$unknown_trans->transaction_date  = '0000-00-00';
				}
                $unknown_trans->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0 ;
                $unknown_trans->remarks = isset($request->remarks) ? ($request->remarks) : '';
                $unknown_trans->created_by = isset($request->created_by) ? ($request->created_by) : 0;
                $unknown_trans->status = isset($request->status) ? ($request->status) : 1;
				
                if($unknown_trans->amount_received >0)
                {
                    if($unknown_trans->save()){
                        $response['status']="Success";
                        $response['msg']=\Lang::get('api.transaction_added');
                        $response['data']=$unknown_trans;
                        return response()->json($response,200);
                    }
                    else {
                        $response['status']="Error";
                        $response['msg']=\Lang::get('api.global_error');
                        return response()->json($response,401);
                    }                    
                }
                else
                {
                    $response['status']="Error";
                    $response['msg'] = "Received Amount Invalid";
                    return response()->json($response,200);
                }
            } 
            catch(\Exception $e){
                $response['status']="Error";
                $response['msg']=\Lang::get('api.global_error');
                return response()->json($response,401);
            }
        }
	}
	
	public function update_unknown_transaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id'=>'required',
            'branch_id'=>'required',
        ]);
        if ($validator->fails()) { 
            $response['status'] = "Error";
            $response['msg'] = $validator->errors()->first();
            return response()->json($response, 200);
        }
        else {
            try {
                if(isset($request->id)) {
                    $unknown_trans = UnknownTransaction::find($request->id); 
                    $unknown_trans->tenant_id = isset($request->tenant_id) ? ($request->tenant_id) : 0;
                    $unknown_trans->branch_id = isset($request->branch_id) ? ($request->branch_id) : 0;
                    if(isset($request->received_date)) {
                        $s_date = ($request->received_date['year']).'-'.sprintf('%02d',($request->received_date['month'])).'-'.sprintf('%02d',($request->received_date['day'])); 
                        $unknown_trans->received_date = date('Y-m-d',strtotime($s_date)); 
                    }
                    $unknown_trans->amount_received = isset($request->amount_received) ? ($request->amount_received) : 0 ;
                    $unknown_trans->payment_type_id = isset($request->payment_type_id) ? ($request->payment_type_id) : 0;   
                    $unknown_trans->debit_to = isset($request->debit_to) ? ($request->debit_to) : 0 ;
                    $unknown_trans->cheque_no = isset($request->cheque_no) ? ($request->cheque_no) : 0 ;
                    if(isset($request->cheque_date)) {
                        $s_date = ($request->cheque_date['year']).'-'.sprintf('%02d',($request->cheque_date['month'])).'-'.sprintf('%02d',($request->cheque_date['day'])); 
                        $unknown_trans->cheque_date = date('Y-m-d',strtotime($s_date)); 
                    }
                    $unknown_trans->bank_id = isset($request->bank_id) ? ($request->bank_id) : 0 ;
                    $unknown_trans->bank_branch_name = isset($request->bank_branch_name) ? ($request->bank_branch_name) : 0 ;
                    if(isset($request->transaction_date)) {
                        $s_date = ($request->transaction_date['year']).'-'.sprintf('%02d',($request->transaction_date['month'])).'-'.sprintf('%02d',($request->transaction_date['day'])); 
                        $unknown_trans->transaction_date = date('Y-m-d',strtotime($s_date)); 
                    }
					if($request->payment_type_id == 2){
							$unknown_trans->status = isset($request->status) ? ($request->status) : 2;
						}
                    $unknown_trans->transaction_no = isset($request->transaction_no) ? ($request->transaction_no) : 0 ;  
                    $unknown_trans->remarks = isset($request->remarks) ? ($request->remarks) : '';
                    $unknown_trans->updated_by = isset($request->updated_by) ? ($request->updated_by) : 0;
                    if($unknown_trans->save()) {
                        $response['status'] = "Success";
                        $response['msg'] = \Lang::get('api.transaction_updated');
                        $response['data'] =$unknown_trans;
                        return response()->json($response,200);
                    }
                    else {
                        $response['status']="Error";
                        $response['msg']=\Lang::get('api.global_error');
                        return response()->json($response,401);
                    }
                }
                else {
                    $response['status'] = "Error";
                    $response['msg'] = \Lang::get('api.record_not_identified');
                    return response()->json($response, 200);
                }
            }
            catch(\Exception $e) { 
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.global_error');
                return response()->json($response, 401);
            }
        }
    }
	
	public function get_unknown_transaction(Request $request) {
        try {
            //id,tenant_id
            $cond = $request->conditions;
            $unknown_trnansactions = UnknownTransaction::where($cond)->orderBy('id','desc')->get();
            foreach($unknown_trnansactions as $unknown_trnansaction) {
                $unknown_trnansaction->tenant_info = isset($unknown_trnansaction->tenant_det) ? ($unknown_trnansaction->tenant_det) : [];
                $unknown_trnansaction->branch_info = isset($unknown_trnansaction->branch_det) ? ($unknown_trnansaction->branch_det) : [] ;
				$unknown_trnansaction->employee_info = isset($unknown_trnansaction->employee_det) ? ($unknown_trnansaction->employee_det) : [] ;
                $unknown_trnansaction->payment_type_info = isset($unknown_trnansaction->payment_type_det) ? ($unknown_trnansaction->payment_type_det) : [] ;
				$unknown_trnansaction->bank_info = isset($unknown_trnansaction->bank_det) ? ($unknown_trnansaction->bank_det) : [];
                $unknown_trnansaction->received_date = ($unknown_trnansaction->received_date!=0) ? date('d/m/Y',strtotime($unknown_trnansaction->received_date)) : '';
				$unknown_trnansaction->cheque_date = ($unknown_trnansaction->cheque_date!=0) ? date('d/m/Y',strtotime($unknown_trnansaction->cheque_date)) : '';
				$unknown_trnansaction->transaction_date = ($unknown_trnansaction->transaction_date!=0) ? date('d/m/Y',strtotime($unknown_trnansaction->transaction_date)) : '';
            }
            $response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $unknown_trnansactions;
            return response()->json($response,200);
        }
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }
	
	 public function list_unknown_transaction(Request $request) {
        try { 
            if(isset($request->tenant_id)) {
                $unknown_trnansactions = UnknownTransaction::where('tenant_id',$request->tenant_id)->orderBy('id','desc')->get();
            }
            else {
                $unknown_trnansactions = UnknownTransaction::orderBy('id','desc')->get();
            }
            $i=1;
            foreach($unknown_trnansactions as $unknown_trnansaction) {
                $unknown_trnansaction->sno = $i;
                $unknown_trnansaction->tenant_info = isset($unknown_trnansaction->tenant_det) ? ($unknown_trnansaction->tenant_det) : [];
                $unknown_trnansaction->branch_info = isset($unknown_trnansaction->branch_det) ? ($unknown_trnansaction->branch_det) : [];
				$unknown_trnansaction->employee_info = isset($unknown_trnansaction->employee_det) ? ($unknown_trnansaction->employee_det) : [] ;
                $unknown_trnansaction->payment_type_info = isset($unknown_trnansaction->payment_type_det) ? ($unknown_trnansaction->payment_type_det) : [];
                $i++;
            }
            $response['status'] = 'Success';
            $response['msg'] = "";
            $response['data'] = $unknown_trnansactions;
            return response()->json($response,200);
        }
        catch(\Exception $e) {
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }
	
	public function delete_unknown_transaction(Request $request) {
        try {
            if(isset($request->id)) {
                $unknown_transaction = UnknownTransaction::find($request->id); 
                $unknown_transaction->deleted_by = $request->deleted_by;
                $unknown_transaction->deletion_remark =  isset($request->deletion_remark) ? ($request->deletion_remark) : 0;
                if($unknown_transaction->save()) {
                    $unknown_transaction->delete();
                     $response['status'] = 'Success';
                    $response['msg'] = \Lang::get('api.transaction_deleted');
                    return response()->json($response,200);
                }
                else {
                    $response['status']="Error";
                    $response['msg']=\Lang::get('api.global_error');
                    return response()->json($response,401);
                }
            }
            else {
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.record_not_identified');
                return response()->json($response, 200);
            }
        }
        catch(\Exception $e) { 
            $response['status'] = "Error";
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }
	
	    Public function auction_wise_to_be_collected_report(Request $request)
    {
    	try
    	{	// id
    		$cond = $request->group_id;
			
    		$chits = ChitDetail::where('group_id',$request->group_id)->get();
			
    		if(count($chits)>0)
    		{	
    			$response_data_array = [];
    			foreach($chits as $cd)
    			{
					$response_data_array_ref = [];
    				$enrollment_id = $cd->id;
    				$ticket_no = $cd->ticket_no;
			        $group_id = $cd->group_id;
			        $customer_id = $cd->customer_id;
					$cust_name = Customer::where('id','=',$customer_id)->pluck('name');
					$group_name=Group::where('id','=',$group_id)->pluck('group_name');
					$cust_mobile_no = Customer::where('id','=',$customer_id)->pluck('mobile_no');
			        if($cd->chit_type != 3)
			        {
						$scheme_id = Group::where('id','=',$group_id)->pluck('scheme_id');
						$chit_value = Scheme::where('id','=',$scheme_id)->pluck('chit_value');
						//total_installment_amount
						$total_installment_amount = BiddingDetail::where('group_id','=',$group_id)->sum('current_installment_amount');
						$cd->overall_installment_amount = $total_installment_amount;
						//paid_amount
						$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
						$paid_rec_amount = $receipt[0]->total_paid;
						$bonus_amount = $receipt[0]->bonus_amount;
						$paid_amount = $paid_rec_amount + $bonus_amount;
						$cd->overall_paid_amount = $paid_amount;
						//pending_amount 
						$pending_amount = $total_installment_amount - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;
						//last auction details
						$max_bid_id = BiddingDetail::where('group_id','=',$group_id)->max('id');
						$current_inst_amt = BiddingDetail::where('id','=',$max_bid_id)->pluck('current_installment_amount');
						//no installment paid 
						$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
					   //auction wise details 
						$auc_wise_inst_amts = BiddingDetail::where('group_id','=',$group_id)->get();
						$i=0;
						$install_det = [];					
						$total_penalty_amounts = 0;
						$total_bonus_amounts = 0;
						foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
							$cust_det = [];		
							$penalty = 0;					
							$bonus = 0;					
							$inst_amt = $auc_wise_inst_amt->current_installment_amount;
							$auction_id = $auc_wise_inst_amt->id;
							$auc_id  = $auc_wise_inst_amt->id;
							$installment_no  = $auc_wise_inst_amt->installment_no;
							$auc_date_inst  = $auc_wise_inst_amt->auction_date;
		                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();
							$max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
							$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
							if(empty($cus_rec_amt[0])) { 
								$amount_paid = 0;
							} 
							else {
							  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
							  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
							  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
							}
							$ins_amt_wise_rec = $inst_amt - $amount_paid;	
							if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
								$receipt_date = $last_receipt_date_inst[0];
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
								$i++;
							} 
							else {
								$receipt_date = $auc_date_inst;
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
							}
							
							// Bonus days and percentage by heirrachy over all 
							if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
							{
							  	$bonus_days = $cd->customer_det->bonus_days;
							  	$bonus_percentage = $cd->customer_det->customer_bonus;
							}    
							elseif($cd->group_det->group_bonus_days!=0 && $cd->group_det->group_base_bonus!=0)
							{
							  	$bonus_days = $cd->group_det->group_bonus_days;
							  	$bonus_percentage = $cd->group_det->group_base_bonus;
							}
							elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
							{
							  	$bonus_days = $cd->branch_det->bonus_days;
							  	$bonus_percentage = $cd->branch_det->bonus;
							}    
							else
							{
							  	$bonus_days = 0;
							  	$bonus_percentage = 0;
							} 	
							if($pending_days <=  $bonus_days)
							{ 
							  	if($ins_amt_wise_rec==$inst_amt)
							  	{ 
									$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
									$bonus = strval(round($bonus_calculation));
									$cust_det['bonus'] = $bonus;
							  	}
							  	else
							  	{
									$cust_det['bonus'] = 0;
							  	}
							}
							else
							{
							  	$cust_det['bonus'] = 0;
							}
							
							// Penalty Calculation installment wise
			                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
			                {
			                  	$inst_penalty_days = $cd->customer_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
			                }
			                elseif($cd->group_det->group_penalty_days!=0 && $cd->group_det->group_base_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->group_det->group_penalty_days;
			                  	$inst_penalty_percentage = $cd->group_det->group_base_penalty;
			                }
			                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->branch_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
			                }
			                else
			                {
			                  	$inst_penalty_days = "0";
			                  	$inst_penalty_percentage = "0";
			                }
			                $pend_days_int = 0;    
			                $cust_det['penalty_amounts'] = 0;   
			                if($pending_days > $inst_penalty_days)
			                {     	
			                  	$branch=Branch::where('id',$cd->branch_id)->get();
			                  	if($cd->prized_status==0)
			                  	{
			                    	$non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
			                    	$pend_days_int = $pending_days * ($non_prize/ 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	} 
			                  	else
			                  	{               
			                    	$prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
			                    	$pend_days_int = $pending_days * ($prize / 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	}                        		  
			                }    
							$cust_det['installment_no'] = $installment_no;
							$cust_det['auction_date'] = $auc_date_inst;
							$cust_det['inst_amt_wise'] = $inst_amt;
							$cust_det['amount_paid'] = $amount_paid;
							$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
							if($inst_amt == $amount_paid)
							{
								$cust_det['pending_days'] = 0;
							}else
							{
								$cust_det['pending_days'] = $pending_days;
							}
							
							$cust_det['cust_name'] = $cust_name[0];
							$cust_det['group_name'] = $group_name[0];
							$cust_det['cust_mobile_no'] = $cust_mobile_no[0];
							$cust_det['ticket_no'] = $ticket_no;
							$cust_det['bonus_inst_wise'] = 0;
							$cust_det['penalty_inst_wise'] = 0;
							$cust_det['discount_inst_wise'] = 0;
							$cust_det['discount_penalty_wise'] = 0;
							$cust_det['auction_id'] = $auction_id;
							$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
							
							array_push($install_det,$cust_det);
							
							$total_penalty_amounts += $penalty;
							$total_bonus_amounts += $bonus;
						};
						$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
						$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
						$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
						$cd->pending_details = $install_det;
							
						array_push($response_data,$cd);		
                        							
			        }
			        if($cd->chit_type == 3)
			        {			        	
						$slab_types = SlabDue::where('id',$cd->slab_id)->get();   
			            $slab_name = isset($slab_types[0]->slab_name) ? ($slab_types[0]->slab_name) : "";
			            $cd->group_name = "Commitment"."-".$slab_name;
			            $chit_value = isset($slab_types[0]->scheme_det) ? (strval(round($slab_types[0]->scheme_det->chit_value))) : "0"; 

						//total_installment_amount
						$total_installment_amount = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->sum('due_amount');
						$cd->overall_installment_amount = $total_installment_amount;
						//paid_amount
						$receipt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where('enrollment_id',$enrollment_id)->where('status',1)->get();
						$paid_rec_amount = $receipt[0]->total_paid;
						$bonus_amount = $receipt[0]->bonus_amount;
						$paid_amount = $paid_rec_amount + $bonus_amount;
						$cd->overall_paid_amount = $paid_amount;
						//pending_amount 
						$pending_amount = $total_installment_amount - $paid_amount;
						$cd->overall_pending_amount = $pending_amount;
						$cd->chit_value = $chit_value;
						//last auction details
						$max_bid_id = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->max('id');
						$current_inst_amt = CommitmentAuction::where('id','=',$max_bid_id)->pluck('due_amount');
						//no installment paid 
						$inst_amt_no = Receipt::where('enrollment_id','=',$enrollment_id)->count();
					   //auction wise details 
						$auc_wise_inst_amts = CommitmentAuction::where('enrollment_id','=',$enrollment_id)->get();
						$i=0;
						$install_det = [];					
						$total_penalty_amounts = 0;
						$total_bonus_amounts = 0;
						foreach($auc_wise_inst_amts as $auc_wise_inst_amt) {
							$cust_det = [];		
							$penalty = 0;					
							$bonus = 0;					
							$inst_amt = $auc_wise_inst_amt->due_amount;
							$auction_id = $auc_wise_inst_amt->id;
							$auc_id  = $auc_wise_inst_amt->id;
							$installment_no  = $auc_wise_inst_amt->installment_no;
							$auc_date_inst  = $auc_wise_inst_amt->auction_date;
		                    $cus_rec_amt=Receipt::selectRaw('sum(received_amount) as total_paid, sum(bonus) as bonus_amount')->where(['enrollment_id' => $enrollment_id, 'auction_id' => $auc_id, 'status' => 1])->get();
							$max_rec_id_inst = Receipt::where('enrollment_id','=',$enrollment_id)->max('id');
							$last_receipt_date_inst = Receipt::where('id','=',$max_rec_id_inst)->pluck('receipt_date');
							if(empty($cus_rec_amt[0])) { 
								$amount_paid = 0;
							} 
							else {
							  	$amount_paid_rec = $cus_rec_amt[0]->total_paid;
							  	$amount_paid_bonus = $cus_rec_amt[0]->bonus_amount;
							  	$amount_paid = $amount_paid_rec + $amount_paid_bonus;
							}
							$ins_amt_wise_rec = $inst_amt - $amount_paid;	
							if($ins_amt_wise_rec !=$inst_amt && $i==0 && $inst_amt_no !=0) {
								$receipt_date = $last_receipt_date_inst[0];
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
								$i++;
							} 
							else {
								$receipt_date = $auc_date_inst;
								$current_date = date('Y-m-d');
								$datetime1 = new DateTime($receipt_date);
								$datetime2 = new DateTime($current_date);
								$interval = $datetime1->diff($datetime2);
								$pending_days = $interval->format('%a');
							}
							
							// Bonus days and percentage by heirrachy over all 
							if($cd->customer_det->bonus_days!=0 && $cd->customer_det->customer_bonus!=0)
							{
							  	$bonus_days = $cd->customer_det->bonus_days;
							  	$bonus_percentage = $cd->customer_det->customer_bonus;
							}    							
							elseif($cd->branch_det->bonus_days!=0 && $cd->branch_det->bonus!=0)
							{
							  	$bonus_days = $cd->branch_det->bonus_days;
							  	$bonus_percentage = $cd->branch_det->bonus;
							}    
							else
							{
							  	$bonus_days = 0;
							  	$bonus_percentage = 0;
							} 	
							if($pending_days <=  $bonus_days)
							{ 
							  	if($ins_amt_wise_rec==$inst_amt)
							  	{ 
									$bonus_calculation = $inst_amt * $bonus_percentage / 100 ; 
									$bonus = strval(round($bonus_calculation));
									$cust_det['bonus'] = $bonus;
							  	}
							  	else
							  	{
									$cust_det['bonus'] = 0;
							  	}
							}
							else
							{
							  	$cust_det['bonus'] = 0;
							}
							
							// Penalty Calculation installment wise
			                if($cd->customer_det->penalty_days!=0 && $cd->customer_det->customer_penalty_interest!=0)
			                {
			                  	$inst_penalty_days = $cd->customer_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->customer_det->customer_penalty_interest;
			                }
			                elseif($cd->branch_det->penalty_days!=0 && $cd->branch_det->branch_wise_penalty!=0)
			                {
			                  	$inst_penalty_days = $cd->branch_det->penalty_days;
			                  	$inst_penalty_percentage = $cd->branch_det->branch_wise_penalty;
			                }
			                else
			                {
			                  	$inst_penalty_days = "0";
			                  	$inst_penalty_percentage = "0";
			                }
			                $pend_days_int = 0;    
			                $cust_det['penalty_amounts'] = 0;   
			                if($pending_days > $inst_penalty_days)
			                {     	
			                  	$branch=Branch::where('id',$cd->branch_id)->get();
			                  	if($cd->prized_status==0)
			                  	{
			                    	$non_prize = isset($branch[0]->non_prize_subscriber_penalty) ? ($branch[0]->non_prize_subscriber_penalty) : 0;
			                    	$pend_days_int = $pending_days * ($non_prize/ 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	} 
			                  	else
			                  	{               
			                    	$prize = isset($branch[0]->prize_subscriber_penalty) ? ($branch[0]->prize_subscriber_penalty) : 0;                     
			                    	$pend_days_int = $pending_days * ($prize / 100);
			                    	$current_month_days = date('t'); 
			                    	$total_days_int = $pend_days_int / $current_month_days;
			                    	$penalty = $total_days_int * $ins_amt_wise_rec;
			                    	$cust_det['penalty_amounts'] = strval(round($penalty));
			                  	}                        		  
			                }    
							$cust_det['installment_no'] = $installment_no;
							$cust_det['auction_date'] = $auc_date_inst;
							$cust_det['inst_amt_wise'] = $inst_amt;
							$cust_det['amount_paid'] = $amount_paid;
							$cust_det['inst_amt_wise_pending'] = $ins_amt_wise_rec;
							if($inst_amt == $amount_paid)
							{
								$cust_det['pending_days'] = 0;
							}else
							{
								$cust_det['pending_days'] = $pending_days;
							}
							$cust_det['bonus_inst_wise'] = 0;
							$cust_det['penalty_inst_wise'] = 0;
							$cust_det['discount_inst_wise'] = 0;
							$cust_det['discount_penalty_wise'] = 0;
							$cust_det['auction_id'] = $auction_id;
							$cust_det['installment_wise_overall_pending'] = $ins_amt_wise_rec + strval(round($penalty)) - $bonus;
							
							array_push($install_det,$cust_det);
							
							$total_penalty_amounts += $penalty;
							$total_bonus_amounts += $bonus;
						};
						$response_data['total_penalty_amounts'] = strval(round($total_penalty_amounts));
						$response_data['total_bonus_amounts'] = strval(round($total_bonus_amounts));
						$response_data['total_overall_pending_amount'] = $pending_amount + $response_data['total_penalty_amounts'] - $response_data['total_bonus_amounts'];
						$cd->pending_details = $install_det;
							
						array_push($response_data,$cd);		
						
			        }
					array_push($response_data_array,$cd);
				}
				
    			$response['status'] = 'Success';
				$response['msg'] = "";
				$response['data'] = $response_data_array;
				return response()->json($response,200);
    		}
    		else
    		{
    			$response['status'] = 'Error';
	            $response['msg'] = \Lang::get('api.no_data_found');
	            return response()->json($response, 200);
    		}
    	}
    	catch(\Exception $e)
    	{
    		$response['status'] = "Error";
    		$response['msg'] = \Lang::get('api.global_error');
    		return response()->json($response,401);
    	}
    }
	public function delete_advance_receipt(Request $request) {
		try {
            if(isset($request->receipt_no)) {
				
                $receipt_list = AdvanceReceipt::where('receipt_no',$request->receipt_no)->get(); 
                foreach($receipt_list as $receipt) {
	                $receipt->deleted_by = $request->deleted_by;
	                $receipt->deletion_remark =  isset($request->deletion_remark) ? ($request->deletion_remark) : 0;
	                if($receipt->save()) {
	                	$receipt->delete();
	                }
	                else {
	                	$response['status'] = 'Error';
			            $response['msg'] = \Lang::get('api.global_error');
			            return response()->json($response, 401);
	                }
                }
                $response['status'] = 'Success';
                $response['msg'] = \Lang::get('api.success_receipt_deleted');
                return response()->json($response,200);
            }
            else {
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.record_not_identified');
                return response()->json($response, 200);
            }
        }
        catch(\Exception $e) { 
            $response['status'] = "Error";
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        } 
    }
	public function delete_commitment_receipt(Request $request) {
		try {
            if(isset($request->receipt_no)) {
				
                $receipt_list = Receipt::where('receipt_no',$request->receipt_no)->get(); 
                foreach($receipt_list as $receipt) {
	                $receipt->deleted_by = $request->deleted_by;
	                $receipt->deletion_remark =  isset($request->deletion_remark) ? ($request->deletion_remark) : 0;
	                if($receipt->save()) {
	                	$receipt->delete();
	                }
	                else {
	                	$response['status'] = 'Error';
			            $response['msg'] = \Lang::get('api.global_error');
			            return response()->json($response, 401);
	                }
                }
                $response['status'] = 'Success';
                $response['msg'] = \Lang::get('api.success_receipt_deleted');
                return response()->json($response,200);
            }
            else {
                $response['status'] = "Error";
                $response['msg'] = \Lang::get('api.record_not_identified');
                return response()->json($response, 200);
            }
        }
        catch(\Exception $e) { 
            $response['status'] = "Error";
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        } 
    }
	
	public function advance_receipt_print(Request $request) {
        try {
            $cond = $request->conditions; 
            $receipts = AdvanceReceipt::selectRaw('*, sum(receipt_amount) as total_paid')->where($cond)->orderBy('id','desc')->get();
			
			foreach($receipts as $receipt) {
				$receipt->tenant_info = isset($receipt->tenant_det) ? ($receipt->tenant_det) : [];                    
                $receipt->branch_info = isset($receipt->branch_det) ? ($receipt->branch_det) : [];
                $receipt->customer_info = isset($receipt->customer_det) ? ($receipt->customer_det) : []; 
                $receipt->group_info = isset($receipt->enrollment_det->group_det) ? ($receipt->enrollment_det->group_det) : [];
                $receipt->enrollment_info = isset($receipt->enrollment_det) ? ($receipt->enrollment_det) : [];	
                $receipt->employee_info = isset($receipt->employee_det) ? ($receipt->employee_det) : [];
				$receipt->payment_type_info = isset($receipt->payment_type_det) ? ($receipt->payment_type_det) : [] ;				
				$adv_rec_dt = date("d-m-Y", strtotime($receipt->receipt_date));
				$receipt->receipt_date = $adv_rec_dt;	
			}
            $response['status'] = 'Success';
            $response['msg'] = '';
            $response['data'] = $receipts;
            return response()->json($response,200);
        }
        catch(\Exception $e) { 
            $response['status'] = 'Error';
            $response['msg'] = \Lang::get('api.global_error');
            return response()->json($response, 401);
        }
    }
}
