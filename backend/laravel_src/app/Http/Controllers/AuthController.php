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
						'Email de recuperacion enviado correctamente',
						null
					);
				}
				else
				{
					$response = HelpController::buildResponse(
						500,
						'Error al enviar el email de recuperacion',
						null
					);
				}  
		}
		else
		{
			$response = HelpController::buildResponse(
				400,
				[
					'email' => [
						'Usuario no encontrado'
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
			// 'password.confirmed' => '',
			// 'password.min' => ''
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
					'Password reseteado exitosamente',
					null
				);
			}
			else if($reset == Password::INVALID_USER)
			{
				$response = HelpController::buildResponse(
					403,
					'User not found',
					null
				);
			}
			else if($reset == Password::INVALID_TOKEN)
			{
				$response = HelpController::buildResponse(
					403,
					'El token es invalido o ya expiro',
					null
				);
			}
			else if($reset == Password::RESET_THROTTLED)
			{
				$response = HelpController::buildResponse(
					500,
					'Error al resetear el password',
					null
				);
			}
			else
			{
				$response = HelpController::buildResponse(
					500,
					'Error al resetear el password',
					null
				);
			}
		}
		else
		{
			$response = HelpController::buildResponse(
				403,
				'El usuario no existe',
				null
			);
		}

		return $response;
	}
		
	public function logIn(Request $request)
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

				$user = Auth::user();

				$user->makeHidden([
					'email_verified_at',
					'country_id',
					'role_id',
					'created_at', 
					'updated_at'
				]);

				$user->country;
				$user->country->makeHidden(['created_at', 'updated_at']);

				$user->role;
				$user->role->makeHidden(['created_at', 'updated_at']);

				$data = [
					'access_token' => $accessToken,
					'user' => $user
				];

				// Log this action
        LoggedactionsController::log(
            Auth::user(),
            'User logged in from IP:' . $request->ip()
        );

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
							'Password incorrecto'
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
						'El usuario no existe'
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

		$user->makeHidden([
			'email_verified_at',
			'country_id',
			'role_id',
			'created_at', 
			'updated_at'
		]);

		$user->country;
		$user->country->makeHidden(['created_at', 'updated_at']);

		$user->role;
		$user->role->makeHidden(['created_at', 'updated_at']);

		$response = HelpController::buildResponse(
			200,
			null,
			$user
		);

		return $response;
	}
}
