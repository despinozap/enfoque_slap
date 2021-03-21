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

  menu: any;

  @Input('loggedUser') loggedUser: User = null as any;
  
  constructor(
    private _authService: AuthService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    
    //For loggedUser
    {
      this._authService.loggedUser$.subscribe((loggedUser) => {
        this.menu = this._utilsService.generateMenu(loggedUser.role_name);

        this.cleanScripts();
        this.loadScript('assets/js/app.js');
      });
    }

    this._authService.notifyLoggedUser();
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
