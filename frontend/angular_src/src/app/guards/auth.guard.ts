import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {

  constructor(
    private _authService: AuthService,
    private router: Router
  ) { }

  canActivate(): boolean
  {
    if(this._authService.getAccessToken() === null)
    {
      this.goTo_login();

      return false;
    }
    else
    {
      return true;
    }
  }

  private goTo_login(): void
  {
    this.router.navigate(['login']);
  }
  
}
