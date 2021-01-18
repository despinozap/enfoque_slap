<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

	public function forgotPassword(Request $request)
	{
		$validatorInput = $request->only('email');
		
		$validatorRules = [
			'email' => 'required|email'
		];

		$validatorMessages = [
			
		];

		$validator = Validator::make(
			$validatorInput,
			$validatorRules,
			$validatorMessages
		);

		if ($validator->fails()) 
		{
			$response = HelpController::buildResponse(
				400,
				$validator->errors(),
				null
			);
		}
		else if(User::where('email', $request->email)->first())
		{
				if(Password::sendResetLink($validatorInput) == Password::RESET_LINK_SENT)
				{
					$response = HelpController::buildResponse(
						200,
						'Password reset email sent successfully',
						null
					);
				}
				else
				{
					$response = HelpController::buildResponse(
						500,
						'Fail on sending the reset password email',
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

	public function resetPassword(Request $request){
			
		$validatorInput = $request->only('email', 'token', 'password', 'password_confirmation');
		
		$validatorRules = [
			'token' => 'required',
			'email' => 'required|email',
			'password' => 'required|confirmed|min:8',
		];

		$validatorMessages = [
			
		];

		$validator = Validator::make(
			$validatorInput,
			$validatorRules,
			$validatorMessages
		);

		if ($validator->fails()) 
		{
			$response = HelpController::buildResponse(
				400,
				$validator->errors(),
				null
			);
		}
		else if(User::where('email', $request->email)->first())
		{
			$reset = Password::reset($validatorInput, function ($user, $password) {
				$user->password = Hash::make($password);
				$user->save();
			});

			if($reset == Password::PASSWORD_RESET)
			{
				$response = HelpController::buildResponse(
					200,
					'Password reseted sucessfully',
					null
				);
			}
			else if($reset == Password::INVALID_USER)
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
			else if($reset == Password::INVALID_TOKEN)
			{
				$response = HelpController::buildResponse(
					403,
					'The token given is invalid or expired',
					null
				);
			}
			else if($reset == Password::RESET_THROTTLED)
			{
				$response = HelpController::buildResponse(
					500,
					'Fail on resetting the password',
					null
				);
			}
			else
			{
				$response = HelpController::buildResponse(
					500,
					'Fail on resetting the password',
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
		
	public function login(Request $request)
	{

		$validatorInput = $request->only('email', 'password');
		
		$validatorRules = [
			'email' => 'required|string',
			'password' => 'required|string'
		];

		$validatorMessages = [
			
		];

		$validator = Validator::make(
			$validatorInput,
			$validatorRules,
			$validatorMessages
		);

		if ($validator->fails()) 
		{
			$response = HelpController::buildResponse(
				400,
				$validator->errors(),
				null
			);
		}
		else if($user = User::where('email', $request->email)->first())
		{
			if(Auth::attempt($validatorInput))
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
		$user = Auth::user();

		$user->makeHidden(['created_at', 'updated_at']);

		$response = HelpController::buildResponse(
			200,
			null,
			$user
		);

		return $response;
	}
}
