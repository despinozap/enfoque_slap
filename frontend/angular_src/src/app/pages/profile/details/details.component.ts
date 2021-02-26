import { Component, OnInit } from '@angular/core';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { threadId } from 'worker_threads';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class ProfileDetailsComponent implements OnInit {

  loggedUser: User = null as any;

  constructor(private _authService: AuthService) {
  }

  ngOnInit(): void {
    
    // For loggedUser
    {
      if(this._authService.getLoggedUser() !== null)
      {
        // If loggedUser is already stored in the service
        this.loggedUser = this._authService.getLoggedUser();
      }
      else
      {
        // Subscribes for getting the loggedUser whenever is stored in the service
        this._authService.loggedUser$.subscribe((loggedUser) => {
          this.loggedUser = loggedUser;
        });
      }
    }
  }

}
