<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class AuthController extends Controller
{
	public function login(Request $request)
	{
		$this->validate($request, [
			'email' => 'required|string',
			'password' => 'required|string',
		]);

		if($user = User::where('email', $request->email)->first())
		{
			$login = [
				'email' => $request->email,
				'password' => $request->password
			];

			if(Auth::attempt($login))
			{
				$accessToken = Auth::user()->createToken('authToken')->accessToken;

				$data = [
					'access_token' => $accessToken,
					'user' => Auth::user()
				];

				$response = HelpController::buildResponse(
					200,
					null,
					$data
				);
			}
			else
			{
				$response = HelpController::buildResponse(
					403,
					[
						'password' => [
							'Invalid password'
						]
					],
					null
				);
			}     
		}
		else
		{
			$response = HelpController::buildResponse(
				403,
				[
					'email' => [
						'User not found'
					]
				],
				null
			);
		}
			
		return $response;
	}

	public function getUser(Request $request)
	{
		$response = HelpController::buildResponse(
			200,
			null,
			Auth::user()
		);

		return $response;
	}
}
