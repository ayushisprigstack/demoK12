<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ManageTicketController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\OperatingSystemController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\StaffMemberController;
use App\Http\Controllers\UtilizerController;
use App\Http\Controllers\AllocationController;
use App\Http\Controllers\RepairPartController;
use App\Http\Controllers\PartsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminAllSchoolController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AddTechnicianController;
use App\Http\Controllers\Admin\AdminDomainController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminInsurancePlanController;
use App\Http\Controllers\SchoolInvoiceController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\SchoolBatchController;
use App\Http\Controllers\CaptchaController;
use FedEx\ShipService\ComplexType;
use FedEx\ShipService\SimpleType;
use FedEx\ShipService\ShipServiceRequest;
use App\Http\Controllers\ManageSoftwareController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\MasterInventoryController;
use App\Http\Controllers\SupportTicketAssignmentController;
use App\Http\Controllers\FedexController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['access.token'])->group(function () {
    Route::get('/middlewareTesting', [LoginController::class, 'middlewareTesting']); 
});

//testing here for github okay

Route::get('/Tickets/{sid?}{gflag}&{key}&{flag}&{skey}&{sflag}&{tflag}&{page}',[ManageTicketController::class, 'Tickets']);
Route::get('/menuAccess/{uid}', [LoginController::class, 'menuAccess']);
Route::post('/register',[LoginController::class,'register']);
Route::post('/addUsers',[LoginController::class,'addUsers']);
Route::get('/allMembers/{sid}&{uid}',[SettingController::class,'allMembers']);

//inventory
Route::post('/upload', [InventoryController::class, 'uploadInventory']); 
Route::get('/exportInventory/{flag}&{sid}', [InventoryController::class, 'exportInventory']); 
Route::post('/manageInventoryAction', [InventoryController::class, 'manageInventoryAction']);
Route::get('/allInventories/{sid}&{flag}&{key}&{skey}&{sflag}&{page}',[InventoryController::class, 'allInventories']);
Route::post('/addUpdateInventory', [InventoryController::class, 'addUpdateInventory']);
Route::get('/getInventoryDataById/{id?}', [InventoryController::class, 'getInventoryDataById']);
//school
Route::post('/addSchool', [SchoolController::class, 'addSchool']);
Route::get('/deleteSchool/{id}', [SchoolController::class, 'deleteSchool']);
//issue
Route::get('/searchInventoryCT/{sid}&{key}', [ManageTicketController::class, 'searchInventoryCT']);
Route::get('/allDeviceIssue', [TicketController::class, 'allIssue']);
Route::post('/generateIssue', [TicketController::class, 'generateIssue']);
Route::post('/importTickets', [TicketController::class, 'importTickets']);
Route::get('/TicketImages/{tid}', [TicketController::class, 'TicketImages']);
Route::get('/allTickets/{sid?}&{uid}', [ManageTicketController::class, 'allTickets']);
Route::get('/getTicketStatus/{flag}', [ManageTicketController::class, 'getTicketStatusforManageTicket']);
Route::post('/changeticketStatus', [ManageTicketController::class, 'changeticketStatus']);
Route::get('/openTickets/{sid?}&{key}&{flag}', [ManageTicketController::class, 'OpenTickets']);
Route::get('/closeTickets/{sid?}&{key}&{flag}', [ManageTicketController::class, 'CloseTickets']);
Route::get('/getTicketNotes/{sid?}&{id}', [ManageTicketController::class, 'getTicketNotes']);
Route::get('/searchOpenTicket/{sid?}&{key}&{flag}', [ManageTicketController::class, 'searchOpenTicket']);
Route::get('/allLonerDevice/{sid}&{key}', [ManageTicketController::class, 'allLonerDevice']);
Route::get('/lonerdeviceHistory/{id}', [ManageTicketController::class, 'lonerdeviceHistory']);
Route::get('/searchLonerforTicket/{sid}&{key}', [ManageTicketController::class, 'searchLonerforTicket']);
Route::get('/sortbyforLoner/{sid}&{key}&{flag}', [ManageTicketController::class, 'sortbyforLoner']);
Route::get('/searchForOpenTicket/{sid}&{skey}', [ManageTicketController::class, 'searchForOpenTicket']);
Route::get('/searchForCloseTicket/{sid}&{skey}', [ManageTicketController::class, 'searchForCloseTicket']);
Route::get('/getallUtilizerforTicket/{sid}&{skey}', [TicketController::class, 'getallUtilizerforTicket']);
Route::get('/closedTicketsPdf/{sid}',[ManageTicketController::class,'closedTicketsPdf']);
Route::post('/editTicketData', [ManageTicketController::class, 'editTicketData']);
Route::get('/TicketData/{tid}&{iid}',[ManageTicketController::class,'TicketData']);
Route::post('/RepairTagPopUpData', [ManageTicketController::class, 'RepairTagPopUpData']);
//Route::get('/Tickets/{sid?}{gflag}&{key}&{flag}&{skey}&{sflag}&{tflag}',[ManageTicketController::class, 'Tickets']);
Route::get('/getTicketDataById/{tid}',[ManageTicketController::class, 'getTicketDataById']);
Route::get('/exportTickets/{sid}',[TicketController::class, 'exportTickets']);
//user
Route::get('/allUser/{sid}&{key}&{skey}&{sflag}',[StaffMemberController::class,'allUser']);
Route::get('/allAccess',[StaffMemberController::class,'allAccess']);
Route::get('/getUserById/{uid?}',[StaffMemberController::class,'updateUserData']);
Route::post('/deleteUser/{id}&{flag}', [StaffMemberController::class, 'deleteUser']);
Route::post('/addUpdateUser', [StaffMemberController::class, 'addUpdateUser']);


//Utilizer 
Route::get('/allUtilizer/{sid}&{skey}',[UtilizerController::class,'allUtilizer']);
Route::post('/deleteUtilizer/{id}', [UtilizerController::class, 'deleteUtilizer']);
Route::get('/utilizerdatabyID/{id}',[UtilizerController::class,'utilizerdatabyID']);
Route::post('/importUtilizer', [UtilizerController::class, 'importUtilizer']);
Route::get('/exportUtilizer/{sid}',[UtilizerController::class,'exportUtilizer']);
Route::get('/UtilizerLog/{sid}',[UtilizerController::class,'UtilizerLog']);
Route::get('/StudentHistory/{sid}&{id}',[UtilizerController::class,'StudentHistory']);
Route::get('/UtilizerData/{sid}&{key}&{skey}&{flag}&{page}',[UtilizerController::class,'UtilizerData']);
Route::post('/AddUpdateUtilizer', [UtilizerController::class, 'AddUpdateUtilizer']);
Route::get('/UtilizerDetailsById/{id}',[UtilizerController::class,'UtilizerDetailsById']);
//Allocation 
Route::get('/allActiveDevice/{sid}&{skey}',[AllocationController::class,'allActiveDevice']);
Route::get('/allActiveUtilizer/{sid}&{skey}',[AllocationController::class,'allActiveUtilizer']);
Route::post('/deviceAllocation', [AllocationController::class, 'deviceAllocation']);

//School
Route::get('/getallSchools/{skey}&{uid}',[SchoolController::class,'GetallSchools']);

//Repairparts
Route::get('/getTickets/{sid}&{skey}',[RepairPartController::class,'getTickets']);
Route::get('/getAllParts/{sid}&{skey}',[RepairPartController::class,'getAllParts']);
Route::get('/getPartsById/{id}',[RepairPartController::class,'getPartsById']);
Route::post('/addAttachment', [RepairPartController::class, 'addAttachment']);
Route::get('/attachpartsHistory/{tid}&{sid}&{iid}',[RepairPartController::class,'attachpartsHistory']);
Route::post('/removeAttachedPart/{tid}&{pid}', [PartsController::class, 'removeAttachedPart']);

//parts
Route::get('/getPartsList/{sid}&{skey}&{flag}',[PartsController::class,'getPartsList']);
Route::post('/addUpdateDeleteParts',[PartsController::class,'addUpdateDeleteParts']);
//Setting

Route::post('/additionalSetting',[SettingController::class,'additionalSetting']);
Route::get('/allSettings/{sid}',[SettingController::class,'allSettings']);
Route::get('/getLogo/{sid}',[SettingController::class,'getLogo']);
Route::post('/addNewNotificatonUser',[SettingController::class,'addNewNotificatonUser']);
//Dashboard
Route::get('/Ticketdata/{sid}',[DashboardController::class,'Ticketdata']);
Route::get('/DashboardData/{sid}&{sdate}&{edate}&{grade}&{building}',[DashboardController::class,'DashboardData']);
//Billing
Route::get('/PaymentDetails/{sid}&{skey}',[BillingController::class,'PaymentDetails']);
Route::get('/PaymentDetailsSortby/{sid}&{skey}&{flag}',[BillingController::class,'PaymentDetailsSortby']);
//admin AdminBillingController
Route::post('/createBatch',[AdminBillingController::class,'createBatch']);
Route::get('/CreatePdfAndStore/{bid}',[AdminAllSchoolController::class,'CreatePdfAndStore']);
Route::get('/fetchAllSchools/{skey}&{uid}&{sortbykey}&{sortbyflag}',[AdminBillingController::class,'fetchAllSchools']);
Route::post('/updateSchoolData',[AdminBillingController::class,'updateSchoolData']);

//reportcontroller 

Route::get('/AdminSideReports/{sdate}&{edate}&{lid}&{sid}',[ReportController::class,'AdminSideReports']);
Route::get('/SchoolSideReports/{sid}&{sdate}&{edate}&{grade}&{building}',[ReportController::class,'SchoolSideReports']);

//admin page school ticket
Route::post('/CreateBatchForAdminPage',[AdminAllSchoolController::class,'CreateBatchForAdminPage']);
Route::get('/getInvoice/{bid}&{iid}',[AdminAllSchoolController::class,'getInvoice']);
Route::get('/PdfDataForBatch/{sid}',[AdminAllSchoolController::class,'PdfDataForBatch']);
Route::get('/DataForBatch/{sid}',[AdminAllSchoolController::class,'DataForBatch']);
Route::post('/InvoicePaymentCheck',[AdminAllSchoolController::class,'InvoicePaymentCheck']);
Route::post('/BatchAmount',[AdminAllSchoolController::class,'BatchAmount']);
Route::get('/TicketAdminPriceData/{tid}',[AdminAllSchoolController::class,'TicketAdminPriceData']);
Route::get('/SendInvoice/{bid}',[AdminAllSchoolController::class,'SendInvoice']);
Route::post('/InvoicePaymentBatchAmountCheck',[AdminAllSchoolController::class,'InvoicePaymentBatchAmountCheck']);
Route::get('/ExtraAttachedDocForBatch/{bid}',[AdminAllSchoolController::class,'ExtraAttachedDocForBatch']);
Route::post('/AdminChangeTicketStatus',[AdminAllSchoolController::class,'AdminChangeTicketStatus']);
Route::get('/AllBatchData/{sid}&{skey}&{sortkey}&{sflag}',[AdminBillingController::class,'AllBatchData']);
//admin dash board 

Route::get('/TechnicianRepairedLog/{id}',[AdminDashboardController::class,'TechnicianRepairedLog']);
Route::get('/adminDashboardData/{sdate}&{edate}&{location}&{school}',[AdminDashboardController::class,'adminDashboardData']);
//school invoice 
Route::get('/showInvoice/{sid}&{skey}',[SchoolInvoiceController::class,'showInvoice']);
Route::post('/paymentDetailsSchoolSide',[SchoolInvoiceController::class,'paymentDetailsSchoolSide']);
Route::get('/downloadReceipt/{iid}',[SchoolInvoiceController::class,'downloadReceipt']);
//k12 admin adduser 

Route::get('/allK12User/{skey}&{sortbykey}&{sortbyflag}', [AddTechnicianController::class, 'allK12User']);
Route::get('/K12UserData/{uid}',[AddTechnicianController::class,'K12UserData']);
Route::get('/deleteK12User/{uid}&{flag}',[AddTechnicianController::class,'deleteK12User']);
Route::post('/addUpdateK12User',[AddTechnicianController::class,'addUpdateK12User']);
Route::get('/setK12LoginasSchoolLogin/{email}&{sid}&{flag}',[AddTechnicianController::class,'setK12LoginasSchoolLogin']);
Route::get('/allLocation',[AddTechnicianController::class,'allLocation']);
//Domain 
Route::post('/AddUpdateDomain',[AdminDomainController::class,'AddUpdateDomain']);
Route::get('/AllDomain/{key}&{flag}',[AdminDomainController::class,'AllDomain']);
Route::get('/DomainDataByID/{id}',[AdminDomainController::class,'DomainDataByID']);
//device allocation
Route::post('/DeviceAllocationToUSer',[AllocationController::class,'DeviceAllocationToUSer']);
Route::get('/allAllocatedDevice/{sid}&{key}',[AllocationController::class,'allAllocatedDevice']);

Route::get('/allInventoriesData/{sid}&{skey}&{flag}',[InventoryController::class,'allInventoriesData']);
Route::get('/allDevice',[InventoryController::class,'allDevice']);

Route::get('/FaqData',[FaqController::class,'FaqData']);

Route::post('/addUpdateSupportTicket',[SupportTicketController::class,'addUpdateSupportTicket']);
Route::get('/getAllSupportTickets/{skey}&{sid}&{uid}&{flag}&{sortkey}&{sflag}',[SupportTicketController::class,'getAllSupportTickets']);
Route::get('/getSupportTicketDataByid/{id}',[SupportTicketController::class,'getSupportTicketDataByid']);
Route::post('/changeSupportTicketStatus',[SupportTicketController::class,'changeSupportTicketStatus']);
Route::post('/addCommentsonSupportTicket',[SupportTicketController::class,'addCommentsonSupportTicket']);
Route::get('/getAllCommentsById/{id}',[SupportTicketController::class,'getAllCommentsById']);
Route::get('/schoolDatabyId/{id}',[SchoolController::class,'schoolDatabyId']);
Route::get('/schoolDatabyNumber/{num}',[SchoolController::class,'schoolDatabyNumber']);
Route::get('/TicketsForShipping/{sid?}{gflag}&{key}&{skey}&{sflag}',[ShippingController::class,'TicketsForShipping']);
Route::post('/create-tracking-code', [ShippingController::class, 'createTrackingCode']);
Route::post('/saveSchoolBatches', [SchoolBatchController::class, 'saveSchoolBatches']);
Route::get('/getAllSchoolBatch/{sid}&{skey}&{sortkey}&{sflag}', [SchoolBatchController::class, 'getAllSchoolBatch']);
Route::post('/calculateTheBatchWeight', [SchoolBatchController::class, 'calculateTheBatchWeight']);
Route::post('addSupportTicketFromLink',[SupportTicketController::class,'addSupportTicketFromLink']);

// location
Route::post('/AddUpdateLocation',[AdminLocationController::class,'AddUpdateLocation']);
Route::get('/LocationAddress/{key}&{skey}&{sflag}',[AdminLocationController::class,'LocationAddress']);
Route::get('/LocationDataByID/{id}',[AdminLocationController::class,'LocationDataByID']);
Route::post('/setmenuAccess',[LoginController::class,'setmenuAccess']);
// Add more routes as needed

//incoming and outgoing
Route::get('/DamageType',[ManageTicketController::class,'DamageType']);
Route::post('/assignSupportTicketTOStaffmember',[SupportTicketController::class,'assignSupportTicketTOStaffmember']);
Route::post('/verify-recaptcha',[CaptchaController::class,'verifyRecaptcha']);
Route::get('/AllTicketsForAdminPanel/{sid?}{gflag}&{key}&{skey}&{sflag}&{bid}',[AdminAllSchoolController::class, 'AllTicketsForAdminPanel']);
Route::post('/deallocateUsers/{sid}&{grade}',[AllocationController::class,'deallocateUsers']);
Route::get('/allGradeandBuilding/{sid}',[AllocationController::class,'allGradeandBuilding']);
//notification
Route::get('/GetAllNotifications/{sid}&{flag}',[SettingController::class,'GetAllNotifications']);
Route::get('/GetEmailsbyId/{sid}&{id}&{skey}',[SettingController::class,'GetEmailsbyId']);
Route::post('/SaveEmails',[SettingController::class,'SaveEmails']);
Route::post('/deleteEmail/{id}&{flag}', [SettingController::class, 'deleteEmail']);
//manage software
Route::get('/GetAllSoftware/{sid}&{searchkey}&{skey}&{sflag}', [ManageSoftwareController::class, 'GetAllSoftware']);
Route::post('/addupdatesoftware', [ManageSoftwareController::class, 'addupdatesoftware']);
Route::get('/GetSoftwareById/{id}', [ManageSoftwareController::class, 'GetSoftwareById']);
Route::get('/GetSoftwareDocument/{id}', [ManageSoftwareController::class, 'GetSoftwareDocument']);
//supportTicket
Route::get('/getTechnologyAndMaintenanceData/{flag}', [SupportTicketController::class, 'getTechnologyAndMaintenanceData']);
//buildings
Route::post('/addUpdateBuildings',[BuildingController::class,'addUpdateBuildings']);
Route::get('/deleteBuilding/{id}',[BuildingController::class,'deleteBuilding']);
Route::get('/allBuildings/{sid}&{skey}&{sortkey}&{sflag}', [BuildingController::class, 'allBuildings']);
Route::get('/getBuildingDataById/{id}',[BuildingController::class,'getBuildingDataById']);
////master inventory
Route::get('/GetAllMasterInventory/{skey}&{flag}', [MasterInventoryController::class, 'GetAllMasterInventory']);
Route::get('/GetMasterInventoryById/{id}', [MasterInventoryController::class, 'GetMasterInventoryById']);
Route::post('/updateMasterInventory', [MasterInventoryController::class, 'updateMasterInventory']);
Route::post('/allupdate', [MasterInventoryController::class, 'allupdate']);
//support ticket assignment
Route::get('/getAllSupportTicketAssignment/{sid}&{skey}', [SupportTicketAssignmentController::class, 'getAllSupportTicketAssignment']);
Route::get('/getSupportTicketAssignmentByID/{sid}', [SupportTicketAssignmentController::class, 'getSupportTicketAssignmentByID']);
Route::post('/addUpdateSupportTicketAssignment', [SupportTicketAssignmentController::class, 'addUpdateSupportTicketAssignment']);
Route::get('/getDeallocateBuildings/{sid}', [SupportTicketAssignmentController::class, 'getDeallocateBuildings']);
//fedex
Route::post('/createShipment', [FedexController::class, 'createShipment']);
Route::get('/PostalCodes', [ShippingController::class, 'PostalCodes']);
Route::get('/getSchoolAddress/{sid}', [SchoolBatchController::class, 'getSchoolAddress']);
Route::get('/getSchoolBatchData/{id}', [SchoolBatchController::class, 'getSchoolBatchData']);
Route::post('/validateShipment', [FedexController::class, 'validateShipment']);
Route::post('/createInvoiceBatchwithFedex', [AdminAllSchoolController::class, 'createInvoiceBatchwithFedex']);
//admin address
Route::post('/AddUpdateLocationAddress', [AdminLocationController::class, 'AddUpdateLocationAddress']);
//Route::get('/getAllLocationAddress/{skey}', [AdminLocationController::class, 'getAllLocationAddress']);
Route::get('/getlocationAddressByID/{id}', [AdminLocationController::class, 'getlocationAddressByID']);
//Test
Route::post('/getAccessToken', [FedexController::class, 'getAccessToken']);
Route::post('/createShipment', [FedexController::class, 'createShipment']);
//insurance
Route::post('/AddInsurancePlan', [AdminInsurancePlanController::class, 'AddInsurancePlan']);
Route::get('/getAllOtherProducts', [AdminInsurancePlanController::class, 'getAllOtherProducts']);
