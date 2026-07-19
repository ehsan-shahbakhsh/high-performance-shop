<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", description: "مستندات جامع وب‌سرویس‌های فروشگاه", title: "High Performance Shop API")]
#[OA\Server(url: "http://localhost:8000/api", description: "سرور اصلی")]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "apiKey",
    description: "توکن احراز هویت (Bearer Token) خود را اینجا وارد کنید",
    name: "Authorization",
    in: "header"
)]
abstract class Controller
{
    //
}
