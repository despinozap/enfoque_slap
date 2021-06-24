import { Component, Input, OnInit } from '@angular/core';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-menubar',
  templateUrl: './menubar.component.html',
  styleUrls: ['./menubar.component.css']
})
export class MenubarComponent implements OnInit {

  loggedUser: any = null;
  private subLoggedUser: any;

  constructor(
    private _authService: AuthService,
  ) { }

  ngOnInit(): void {
    
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        if(data.receiver === this._authService.NOTIFICATION_RECEIVER_HOME)
        {
          this.loggedUser = data.user;

          this.cleanScripts();
          this.loadScript('assets/js/app.js');
        }
      });

      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_HOME);
    }    
  }

  ngOnDestroy(): void {
    this.subLoggedUser.unsubscribe();
  }

  private cleanScripts(): void {
    document.querySelectorAll('script')
      .forEach((script) => {
        script.remove();
      });
  }

  private loadScript(src: string) {
    var script = document.createElement("script");
    script.setAttribute("src", src);
    document.body.appendChild(script);
  }

}
