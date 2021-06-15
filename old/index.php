<?PHP

// just snippets of decent code no longer needed


// // Flight::route("POST /forgot", function () {
// //     $data = Flight::request()->data;
// //     $email = $data['reset_email'];

// //     try {
// //         $controller = new App\Controllers\AuthenticationController(Flight::request(), Flight::mail());

// //         Flight::hxheader('Request has been generated');
// //     }
// //     catch (\Delight\Auth\InvalidEmailException $e) {
// //         Flight::hxheader('Invalid email address');
// //     }
// //     catch (\Delight\Auth\EmailNotVerifiedException $e) {
// //         Flight::hxheader('Email not verified');
// //     }
// //     catch (\Delight\Auth\ResetDisabledException $e) {
// //         Flight::hxheader('Password reset is disabled');
// //     }
// //     catch (\Delight\Auth\TooManyRequestsException $e) {
// //         Flight::hxheader('Too many requests');
// //     } catch (\App\Exceptions\FormException $e) {
// //         Flight::hxheader($e->getMessage(), 'error');
// //     } catch (Exception $e) {
// //         Flight::hxheader("There was an error", "error", $e);
// //     }
// // });

// // too much complication for this small project
// // Flight::route(' GET /verify-email', function () {
// //     $data = Flight::request()->query;
// //     $selector = $data['selector'];
// //     $token = $data['token'];
// //     Debugger::log(['let us verify', $selector, $token]);

// //     try {
// //         Flight::auth()->confirmEmail($selector, $token);
// //         echo 'Email address has been verified';
// //         $message = "success";
// //     } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
// //         Flight::hxheader('Invalid token');
// //         $message = "invalid";
// //     } catch (\Delight\Auth\TokenExpiredException $e) {
// //         Flight::hxheader('Token expired');
// //         $message = "invalid";
// //     } catch (\Delight\Auth\UserAlreadyExistsException $e) {
// //         Flight::hxheader('Email address already exists');
// //         $message = "Email address already exists";
// //     } catch (\Delight\Auth\TooManyRequestsException $e) {
// //         Flight::hxheader('Too many requests');
// //         $message = "Try again later";
// //     } catch (Exception $e) {
// //         $message = "Something went wrong.";
// //         Debugger::log($e->getMessage());
// //     }

// //     Flight::redirect(Flight::url()->sign("/login?message={$message}"));
// // });


