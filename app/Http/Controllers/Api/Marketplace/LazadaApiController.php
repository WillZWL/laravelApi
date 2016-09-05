<?php

namespace App\Http\Controllers\Api\Marketplace;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\ApiLazadaService;
use PDF;
class LazadaApiController extends Controller
{
    private $apiLazadaService;
    
    public function __construct(ApiLazadaService $apiLazadaService)
    {
        $this->apiLazadaService = $apiLazadaService;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getShipmentProviders(Request $request)
    {
        //
        $storeName = "BCLAZADAMY";
        return $this->apiLazadaService->getShipmentProviders($storeName);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDoucment(Request $request)
    {
       $this->apiLazadaService->getDocument($storeName,$orderItemIds,$documentType);
    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       /* $esgOder = "22";
        if($esgOder){
            $doucment[] = $this->apiLazadaService->allocatedOrderFufillment($storeName,$esgOrder);
        }*/

        $aa="PGh0bWw+CjxoZWFkPgo8c3R5bGU+LnByaW50LXBhbmVsLXNlY3Rpb24gLnByaW50LXBhZ2UgewogIC13ZWJraXQgdHJhbnNmb3JtOiByb3RhdGUoOTBkZWcpOwp9CnAgewogICAgbWFyZ2luOiAwOwogICAgcGFkZGluZzogMDsKfQppbWcgewogICAgbWFyZ2luLXJpZ2h0OiAxMTBweDsKICAgIG1hcmdpbi10b3A6IC0zMHB4Owp9Ci5tZXJjaGFudGluZm8gewogICAgcG9zaXRpb246IHJlbGF0aXZlOwogICAgbGluZS1oZWlnaHQ6IDEwcHg7CiAgICBmb250LXNpemU6IDE0cHg7CiAgICBib3JkZXItYm90dG9tOiAxcHggc29saWQgIzAwMDsKfQoubWVyY2hhbnRpbmZvLCAubWVyY2hhbnRpbmZvX2NvbnRlbnQgewogICAgd2lkdGg6IDcwMHB4OwogICAgaGVpZ2h0OiAxMTJweDsKfQoubWVyY2hhbnRpbmZvX2NvbnRlbnQgewogICAgcG9zaXRpb246IGFic29sdXRlOwogICAgbGVmdDogMDsKICAgIHRvcDogMDsKICAgIHotaW5kZXg6IDI7Cn0KLmxvZ28gewogICAgcG9zaXRpb246IHJlbGF0aXZlOwp9Ci5sb2dvLCAubG9nb19jb250ZW50IHsKICAgIHdpZHRoOiA3MDBweDsKICAgIGhlaWdodDogMTEycHg7Cn0KLmxvZ29fY29udGVudCB7CiAgICBwb3NpdGlvbjogYWJzb2x1dGU7CiAgICBsZWZ0OiAwOwogICAgdG9wOiAwOwogICAgei1pbmRleDogMjsKfQouYWRkcmVzc19saW5lLCAuYWRkcmVzc19ibG9jayB7CiAgICBsaXN0LXN0eWxlLXR5cGU6IG5vbmU7CiAgICBtYXJnaW46IDA7IHBhZGRpbmc6IDA7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAgZm9udC1zaXplOiAxMXB4OwogICAgZm9udC1mYW1pbHk6IEFyaWFsLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7CiAgICBsaW5lLWhlaWdodDogMS4zOwp9Ci5hZGRyZXNzX2xpbmUgbGkgewogICAgZmxvYXQ6IGxlZnQ7CiAgICBkaXNwbGF5OiBpbmxpbmU7CiAgICBtYXJnaW4tcmlnaHQ6IDEwcHg7Cn0gCi5sb2dvIC5hZGRyZXNzX2xpbmUgewogICAgcGFkZGluZy10b3A6IDg4cHg7CiAgICBmbG9hdDogbGVmdDsKICAgIHdpZHRoOiA3MCU7Cn0KLmxvZ28gLmFkZHJlc3NfYmxvY2sgewogICAgZmxvYXQ6IHJpZ2h0OwogICAgd2lkdGg6IDI5JTsKICAgIHBhZGRpbmctdG9wOiA1OXB4Owp9Ci5hZGRyZXNzX2Jsb2NrIGxpIHsKICAgIG92ZXJmbG93OiBoaWRkZW47Cn0KLmFkZHJlc3NfYmxvY2sgbGkgLmxhYmVsIHsKICAgIGZsb2F0OiBsZWZ0OwogICAgd2lkdGg6IDUwcHg7Cn0KLmNvbnRhaW5lciB7CiAgICBmb250LXNpemU6IDEzcHg7CiAgICBmb250LWZhbWlseTogQXJpYWwsIEhlbHZldGljYSwgc2Fucy1zZXJpZjsKICAgIGxpbmUtaGVpZ2h0OiAxLjU7CiAgICBtYXJnaW46IDAgMCAxNXB4IDA7CiAgICB3aWR0aDogNzAwcHg7Cn0KLmFkZHJlc3NfYmxvY2tfbGFyZ2UgewogICAgbWFyZ2luLXRvcDogNTBweDsKfQouYWRkcmVzc19ibG9ja19sYXJnZSB1bCB7CiAgICBsaXN0LXN0eWxlLXR5cGU6IG5vbmU7CiAgICBtYXJnaW46IDA7CiAgICBwYWRkaW5nOiAwOwp9Ci5hZGRyZXNzX2Jsb2NrX2xhcmdlIHVsIGxpIHsKICAgIG1hcmdpbjogMDsKICAgIHBhZGRpbmc6IDA7Cn0KLmRhdGFncmlkX2hlYWRlciB7CiAgICBvdmVyZmxvdzogaGlkZGVuOwogICAgbWFyZ2luLWJvdHRvbTogMTBweDsKfQouZGF0YWdyaWRfaGVhZGVyIC5oZWFkbGluZSB7CiAgICBmbG9hdDogbGVmdDsKICAgIHdpZHRoOiA0OSU7CiAgICBwYWRkaW5nLXRvcDogMTZweDsKfQouZGF0YWdyaWRfaGVhZGVyIC5kZXRhaWxzIHsKICAgIGZsb2F0OiByaWdodDsKICAgIHdpZHRoOiA0OSU7CiAgICB0ZXh0LWFsaWduOiByaWdodDsKfQouaGVhZGxpbmUgewogICAgZm9udC1zaXplOiAxNnB4OwogICAgZm9udC13ZWlnaHQ6IGJvbGQ7Cn0KLmRhdGFncmlkIHsKICAgIG1hcmdpbi1ib3R0b206IDEwcHg7Cn0KLmRhdGFncmlkIHRhYmxlIHsKICAgIHdpZHRoOiAxMDAlOwogICAgYm9yZGVyLWNvbGxhcHNlOiBjb2xsYXBzZTsKfQouZGF0YWdyaWQgdGgsIC5kYXRhZ3JpZCB0ZCB7CiAgICBtYXJnaW46IDA7CiAgICBwYWRkaW5nOiA1cHggMTBweDsKICAgIHRleHQtYWxpZ246IGxlZnQ7CiAgICB2ZXJ0aWNhbC1hbGlnbjogdG9wOwogICAgZm9udC1zaXplOiAxM3B4OwogICAgZm9udC1mYW1pbHk6IEFyaWFsLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7CiAgICBsaW5lLWhlaWdodDogMS41Owp9Ci5kYXRhZ3JpZCB0aCB7CiAgICBmb250LXdlaWdodDogYm9sZDsKICAgIGJvcmRlci10b3A6IDNweCBzb2xpZCAjQ0NDQ0NDOwogICAgYm9yZGVyLWJvdHRvbTogM3B4IHNvbGlkICNDQ0NDQ0M7Cn0KLmRhdGFncmlkIHRkIHsKICAgIGJvcmRlci1ib3R0b206IDJweCBzb2xpZCAjQ0NDQ0NDOwp9Ci5zdW1tYXJ5X3dyYXAgewogICAgb3ZlcmZsb3c6IGhpZGRlbjsKfQouc3VtbWFyeSB7CiAgICBmbG9hdDogcmlnaHQ7CiAgICB3aWR0aDogMzUlOwp9Ci5zdW1tYXJ5IHVsIHsKICAgIGxpc3Qtc3R5bGUtdHlwZTogbm9uZTsKICAgIG1hcmdpbjogMDsgcGFkZGluZzogMDsKfQouc3VtbWFyeSB1bCBsaSB7CiAgICBtYXJnaW46IDA7CiAgICBwYWRkaW5nOiAwIDAgNXB4IDA7CiAgICBvdmVyZmxvdzogaGlkZGVuOwp9Ci5zdW1tYXJ5IHVsIGxpIC5sYWJlbCB7CiAgICBmbG9hdDogbGVmdDsKICAgIHdpZHRoOiA1MCU7Cn0KLnN1bW1hcnkgdWwgbGkgLnZhbHVlIHsKICAgIGZsb2F0OiByaWdodDsKICAgIHdpZHRoOiA0OSU7CiAgICB0ZXh0LWFsaWduOiByaWdodDsKfQouc3VtbWFyeSAudG90YWwgewogICAgYm9yZGVyLXRvcDogMXB4IHNvbGlkOwogICAgYm9yZGVyLWJvdHRvbS1zdHlsZTogZG91YmxlOwogICAgZm9udC13ZWlnaHQ6IGJvbGQ7Cn0KLmZvb3RlciB7CiAgICBjbGVhcjogYm90aDsKICAgIHdpZHRoOiA3MDBweDsKICAgIHRleHQtYWxpZ246IGNlbnRlcjsKICAgIGJvcmRlci10b3A6IDRweCBzb2xpZCAjZjJmMmYyOwp9Ci5mb290ZXIgLmFkZHJlc3NfbGluZSB7CiAgICBwYWRkaW5nLXRvcDogNXB4Owp9Ci5mb290ZXIgLmFkZHJlc3NfbGluZSBsaSB7CiAgICBkaXNwbGF5OiBpbmxpbmUtYmxvY2s7CiAgICBmbG9hdDogbm9uZTsKfQouZ3N0dGFibGUgewogICAgYm9yZGVyLWNvbGxhcHNlOiBjb2xsYXBzZTsKfQouZ3N0dGFibGUsIGdzdHRkLCBnc3R0aCB7CiAgICBib3JkZXI6IDFweCBzb2xpZCBibGFjazsKICAgIHBhZGRpbmc6IDVweDsKfQouYWRkcmVzc3RhYmxlIHsKICAgIG1hcmdpbjogMjBweCAwIDAgMDsKfQouYWRkcmVzc3J1bGVzIHsKICAgIGZvbnQ6IDEycHggQXJpYWw7CiAgICBib3JkZXI6IG5vbmU7CiAgICBib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlOwp9Ci5hZGRyZXNzcnVsZXMgdGQgewogICAgcGFkZGluZzogNXB4Owp9Ci5hZGRyZXNzcnVsZXMgLnRhYmxlSGVhZGVyIHRkIHsKICAgIGJvcmRlci10b3A6IDFweCBzb2xpZCAjYWFhOwogICAgZm9udC13ZWlnaHQ6IGJvbGQ7Cn0gIAouYWRkcmVzc3J1bGVzIC50YWJsZVJvdyB0ZCB7CiAgICBib3JkZXI6IG5vbmU7Cn0KLmFkZHJlc3NydWxlcyAudGFibGVGb290ZXIgdGQgewogICAgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICNhYWE7Cn0KLmFkZHJlc3NydWxlcyAudGFibGVIZWFkZXIgLnNwYWNlckNvbHVtbiwgLmFkZHJlc3N0YWJsZSAudGFibGVSb3cgLnNwYWNlckNvbHVtbiwgLmFkZHJlc3N0YWJsZSAudGFibGVGb290ZXIgLnNwYWNlckNvbHVtbiB7CiAgICB3aWR0aDogMzBweDsKICAgIGJvcmRlcjogbm9uZTsKICAgIGJhY2tncm91bmQ6ICNmZmY7Cn0KICAgICAgCiNjdXN0b21lci1uYW1lLWFuZC1zaGlwcGluZyB1bCB7CiAgICBsaXN0LXN0eWxlLXBvc2l0aW9uOiBvdXRzaWRlOwogICAgbWFyZ2luLWxlZnQ6IC00MHB4Owp9CiAgICAKI2N1c3RvbWVyLW5hbWUtYW5kLXNoaXBwaW5nIGxpIHsKICAgIGxpc3Qtc3R5bGU6IG5vbmU7CiAgICBsaXN0LXN0eWxlLXBvc2l0aW9uOiBvdXRzaWRlOwogICAgbWFyZ2luLWxlZnQ6IDA7Cn0KPC9zdHlsZT4KPG1ldGEgY2hhcnNldD0iVVRGLTgiPgo8L2hlYWQ+Cjxib2R5Pgo8ZGl2IGNsYXNzPSJsb2dvIj4KPGRpdiBjbGFzcz0ibG9nbyI+CjxkaXYgY2xhc3M9Im1lcmNoYW50aW5mbyI+CjxkaXY+CjxzdHJvbmc+RS1TZXJ2aWNlIEdyb3VwIExpbWl0ZWQ8L3N0cm9uZz48YnI+Cjxicj4KVW5pdCBBLCAxMC9GLCBXYWggU2hpbmcgSW5kdXN0cmlhbCBCdWlsZGluZywgMTggQ2hldW5nIFNodW4gU3RyZWV0LCBMYWkgQ2hpIEtvaywgS293bG9vbis4NiAxODMxOTkxMDMwPGJyPgo8YnI+CkdTVCBSZWdpc3RyYXRpb24gTm8uOiA8L2Rpdj4KPC9kaXY+CsKgCgo8ZGl2Pgo8ZGl2IHN0eWxlPSJ0ZXh0LWFsaWduOiBjZW50ZXI7Ij48c3Ryb25nPlRBWCBJTlZPSUNFPC9zdHJvbmc+PC9kaXY+CjwvZGl2PgoKPGRpdj4KPHRhYmxlIHN0eWxlPSJ3aWR0aDoxMDAlIj4KCTx0Ym9keT4KCQk8dHI+CgkJPC90cj4KCQk8dHI+CgkJCTx0ZD48c3Ryb25nPkludm9pY2UgTnVtYmVyOjwvc3Ryb25nPjwvdGQ+CgkJCTx0ZD7CoDwvdGQ+CgkJCTx0ZD4yNDA0MDI8L3RkPgoJCTwvdHI+CgkJPHRyPgoJCQk8dGQ+PHN0cm9uZz5PcmRlciBOdW1iZXI6PC9zdHJvbmc+PC90ZD4KCQkJPHRkPsKgPC90ZD4KCQkJPHRkPjM3NTY1MjIxNjwvdGQ+CgkJPC90cj4KCQk8dHI+CgkJCTx0ZD48c3Ryb25nPk9yZGVyIERhdGU6PC9zdHJvbmc+PC90ZD4KCQkJPHRkPsKgPC90ZD4KCQkJPHRkPjAzIFNlcCAyMDE2PC90ZD4KCQk8L3RyPgoJCTx0cj4KCQkJPHRkPjxzdHJvbmc+SW52b2ljZSBUbzo8L3N0cm9uZz48L3RkPgoJCQk8dGQ+wqA8L3RkPgoJCQk8dGQ+Q2hpb25nIENoaWV3IEJpbmcgPC90ZD4KCQk8L3RyPgoJCTx0cj4KCQkJPHRkPjxzdHJvbmc+SW52b2ljZSBEYXRlOjwvc3Ryb25nPjwvdGQ+CgkJCTx0ZD7CoDwvdGQ+CgkJCTx0ZD4wNSBTZXAgMjAxNjwvdGQ+CgkJPC90cj4KCTwvdGJvZHk+CjwvdGFibGU+CsKgCgo8cD7CoDwvcD4KCjxwPjxpbWcgc3JjPSJkYXRhOmltYWdlL3BuZztiYXNlNjQsaVZCT1J3MEtHZ29BQUFBTlNVaEVVZ0FBQU1NQUFBQStDQUlBQUFBWkVUZzZBQUFBQ1hCSVdYTUFBQTdFQUFBT3hBR1ZLdzRiQUFBQnZrbEVRVlI0bk8zZHpVN0VJQlJBWVRDKy95dnJ3a2dJOTBLcG5qalNuRzloR29iK2pEMWluWTNsWTZtVU1teVhiM0Y3OGVwd2hQVHI1bG1HTTdhOTRwV2tSN3VjVHgxL2NjeGI4MmZYYy9mNGkrdmZ2NzhMYjBVaVdKSVlsaVNHSllsaFNXSllraGlXSklZbGlXRkpZbGlTR0pZa2hpV0pZVWxpV0pJWWxpU0dKWWxoU1dKWWtoaVdKSVlsaVdGSllsaVNHSllraGlXSllVbGlXSklZbGlTR0pZbGhTV0pZa2hpV0pJWWxpV0ZKWWxpU0dKWWtoaVdKWVVsaVdKSVlsaVNHSllsaFNXSllraGlXSklZbGlXRkpZbGlTR0pZa2hpV0pZVWxpV0pJWXRmOG5sZEtQdmIvNkFoaTExcmI5OWJQUmo3VHhkSEIvOTNUbWJMQ054NUU0OHdIT1c1TnFyZTJtOW5jM2J2ZTdsSzZrMmF1L254bTNTd2d1blRsc25PaThOU25lNXZWM2Y3RXd4QjNUZEM1UGtaNDBYUUxURTgzQ1BjdGhKZlgzSmwwQXlzYjlhTHYwYTBBYXpUQXpQZkwrWUhxaTJUczZ6bUVsemZSQnhENTJWcStkYVBwamx1MTFibjJpOWVTREhGYlM0bUhvYjF3K1A5MzE4bmRFT2UvenBMWm10R1hnNitjN25WbkN5ckY0ZGhuMmpUUHhqTm9SaG5kMG9vY3NzSXMvem1jUDBYSG01ZUQrSnd1M1BrUjRob2VVcEpjNzc3ZWIvcWRQNDZ3a2FuYmMrMnNBQUFBQVNVVk9SSzVDWUlJPSIgYWx0PSJPcmRlciBOdW1iZXIiPjwvcD4KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJhZGRyZXNzdGFibGUiPgo8dGFibGUgY2xhc3M9ImFkZHJlc3NydWxlcyIgc3R5bGU9IndpZHRoOjEwMCUiPgoJPHRib2R5PgoJCTx0ciBjbGFzcz0idGFibGVIZWFkZXIiPgoJCQk8dGQ+QklMTElORyBBRERSRVNTPC90ZD4KCQkJPHRkIGNsYXNzPSJzcGFjZXJDb2x1bW4iPsKgPC90ZD4KCQkJPHRkPlNISVBQSU5HIEFERFJFU1M8L3RkPgoJCTwvdHI+CgkJPHRyPgoJCQk8dGQgdmFsaWduPSJ0b3AiPkxvdCA2ODU1LCBKYWxhbiBBbHBpbmUsIFJpYW0gTGFtYSwsU2FyYXdhay1NaXJpLTk4MDAwLE1hbGF5c2lhPC90ZD4KCQkJPHRkIGNsYXNzPSJzcGFjZXJDb2x1bW4iPsKgPC90ZD4KCQkJPHRkIHZhbGlnbj0idG9wIj5Mb3QgNjg1NSwgSmFsYW4gQWxwaW5lLCBSaWFtIExhbWEsLFNhcmF3YWstTWlyaS05ODAwMCxNYWxheXNpYTwvdGQ+CgkJPC90cj4KCQk8dHIgY2xhc3M9InRhYmxlRm9vdGVyIj4KCQkJPHRkPgo8c3Ryb25nPkNvbnRhY3QgUGhvbmU6IDwvc3Ryb25nPjAxMjg4MTY4OTY8L3RkPgoJCQk8dGQgY2xhc3M9InNwYWNlckNvbHVtbiI+wqA8L3RkPgoJCQk8dGQ+CjxzdHJvbmc+Q29udGFjdCBQaG9uZTogPC9zdHJvbmc+MDEyODgxNjg5NjwvdGQ+CgkJPC90cj4KCTwvdGJvZHk+CjwvdGFibGU+CjwvZGl2PgoKPGRpdj4KPHN0cm9uZz5QYXltZW50IE1ldGhvZDogPC9zdHJvbmc+SVBheTg4PC9kaXY+Cgo8ZGl2IGNsYXNzPSJkYXRhZ3JpZF9oZWFkZXIgY29udGFpbmVyIj4KPHAgc3R5bGU9IndpZHRoOiAzNDNweDsiPllvdXIgb3JkZXJlZCBpdGVtcyBmb3LCoDM3NTY1MjIxNjwvcD4KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJjb250YWluZXIgZGF0YWdyaWQiPjx0YWJsZT4KPHRoZWFkPjx0cj4KPHRoPiM8L3RoPgo8dGg+UHJvZHVjdCBuYW1lPC90aD4KPHRoPlNlbGxlciBTS1U8L3RoPiA8dGg+U2hvcCBTS1U8L3RoPiA8dGg+UHJpY2U8L3RoPgo8dGg+UGFpZCBQcmljZTwvdGg+CjwvdHI+PC90aGVhZD4KPHRib2R5Pgo8IS0tICAtLT48dHI+Cjx0ZD4xPC90ZD4KPHRkPk1JTklYIE5FTyBVMSBMYXRlc3QgVWx0cmEgNEsgSEQgQW5kcm9pZCA2NC1CaXQgVFYgQm94IFN0cmVhbWluZwpNZWRpYSBQbGF5ZXIgd2l0aCBBMiBMaXRlIFdpcmVsZXNzIE1vdXNlIFJlbW90ZSBDb250cm9sPC90ZD4KPHRkPjE5MDI4LVVLLU5BPC90ZD4gPHRkPk1JNDI4RUxBQTdFSkYwQU5NWS0xNTY4NDQxNzwvdGQ+Cjx0ZCBjbGFzcz0icHJpY2UtdmFsdWUiPjU4OS4wMDwvdGQ+Cjx0ZCBjbGFzcz0icHJpY2UtdmFsdWUiPjU4OS4wMDwvdGQ+CjwvdHI+CjwhLS0gIC0tPgo8L3Rib2R5Pgo8L3RhYmxlPjwvZGl2PgoKPGRpdiBjbGFzcz0iY29udGFpbmVyIHN1bW1hcnlfd3JhcCI+CjxkaXYgY2xhc3M9InN1bW1hcnkiPgo8dWw+Cgk8bGk+CjxzcGFuIGNsYXNzPSJsYWJlbCI+PHN0cm9uZz5TdWJ0b3RhbCAoaW5jbCBHU1QpOjwvc3Ryb25nPjwvc3Bhbj4gPHNwYW4gY2xhc3M9InZhbHVlIj48c3Ryb25nPjxlbT5STTwvZW0+IDU4OS4wMDwvc3Ryb25nPjwvc3Bhbj4KPC9saT4KCTxsaSBzdHlsZT0iYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkIj4KPHNwYW4gY2xhc3M9ImxhYmVsIj5MZXNzOiBWb3VjaGVyIGFwcGxpZWQ6PC9zcGFuPiA8c3BhbiBjbGFzcz0idmFsdWUiPjxlbT4tUk08L2VtPiAwLjAwPC9zcGFuPgo8L2xpPgoJPGxpPgo8c3BhbiBjbGFzcz0ibGFiZWwiPjxzdHJvbmc+VG90YWwgKGluY2wgR1NUKTo8L3N0cm9uZz48L3NwYW4+IDxzcGFuIGNsYXNzPSJ2YWx1ZSI+PHN0cm9uZz48ZW0+Uk08L2VtPiA8c3Bhbj41ODkuMDA8L3NwYW4+PC9zdHJvbmc+PC9zcGFuPgo8L2xpPgoJPGxpPgo8c3BhbiBjbGFzcz0ibGFiZWwiPlNoaXBwaW5nIChpbmNsIEdTVCk6PC9zcGFuPiA8c3BhbiBjbGFzcz0idmFsdWUiPjxlbT4rUk08L2VtPiA8c3Bhbj4wLjAwPC9zcGFuPjwvc3Bhbj4KPC9saT4KCTxsaSBjbGFzcz0idG90YWwiPgo8c3BhbiBjbGFzcz0ibGFiZWwiPjxzdHJvbmc+TmV0IFBhaWQ6PC9zdHJvbmc+PC9zcGFuPiA8c3BhbiBjbGFzcz0idmFsdWUiPjxzdHJvbmc+PGVtPlJNPC9lbT4gPHNwYW4+NTg5LjAwPC9zcGFuPjwvc3Ryb25nPjwvc3Bhbj4KPC9saT4KCTxsaT4KPHNwYW4gY2xhc3M9ImxhYmVsIj5Ub3RhbCAoaW5jbCBHU1QpOjwvc3Bhbj4gPHNwYW4gY2xhc3M9InZhbHVlIj48ZW0+Uk08L2VtPiA8c3Bhbj41ODkuMDA8L3NwYW4+PC9zcGFuPgo8L2xpPgoJPGxpPgo8c3BhbiBjbGFzcz0ibGFiZWwiPipHU1Q8L3NwYW4+IDxzcGFuIGNsYXNzPSJ2YWx1ZSI+PGVtPi1STTwvZW0+IDAuMDA8L3NwYW4+CjwvbGk+Cgk8bGkgY2xhc3M9InRvdGFsIj4KPHNwYW4gY2xhc3M9ImxhYmVsIj48c3Ryb25nPlRvdGFsIChleGNsIEdTVCk8L3N0cm9uZz48L3NwYW4+IDxzcGFuIGNsYXNzPSJ2YWx1ZSI+PHN0cm9uZz48ZW0+Uk08L2VtPiA1ODkuMDA8L3N0cm9uZz48L3NwYW4+CjwvbGk+Cgk8bGk+KkdTVCBBcHBsaWVkIFdoZXJlIEFwcGxpY2FibGU8L2xpPgo8L3VsPgo8L2Rpdj4KPC9kaXY+CjwvZGl2Pgo8L2Rpdj4KPC9ib2R5Pgo8L2h0bWw+";
        
        
        $fileData = base64_decode($aa);
$patterns = array();
$patterns[0] = '/class="logo"/';
$replacements = array();
$replacements[2] = 'class="page"';
$str= preg_replace($patterns, $replacements, $fileData,2);
        $fileData2 = "<style type='text/css'>
    .page {
        overflow: hidden;
        page-break-after: always;
    }
</style><div class='page'>".$str."</div><div class='page'>".$str."</div>";

       // print_r( $fileData);exit();
       // print_r( $fileData2);exit();

           //$pdfFile= \Storage::disk('xml')->getDriver()->getAdapter()->getPathPrefix()."3.pdf";
        return  PDF::loadHTML($fileData2)->inline('1.pdf');
       //return  PDF::loadHTML("<h2>123</h2><h3>123123</h3>")->download("1.pdf");
    $pdf = \App::make('snappy.pdf.wrapper');
    $pdf->generateFromHtml($fileData, $pdfFile,[],$overwrite = true);
    return $pdf->stream();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
