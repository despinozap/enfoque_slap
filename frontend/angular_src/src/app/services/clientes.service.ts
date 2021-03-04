import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ClientesService {

  constructor(private httpClient: HttpClient) { }

  public getClientes(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/all`;
    
    return this.httpClient.get(endpoint);
  }
}
