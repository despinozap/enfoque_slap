import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class FaenasService {

  constructor(private httpClient: HttpClient) { }

  public getFaenas(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/faenas/all`;
    
    return this.httpClient.get(endpoint);
  }
}
