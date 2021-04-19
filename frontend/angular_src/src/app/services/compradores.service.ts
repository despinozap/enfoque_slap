import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CompradoresService {

  constructor(
    private httpClient: HttpClient
  ) { }

  public getComprador(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getCompradores(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores`;
    
    return this.httpClient.get(endpoint);
  }
}
