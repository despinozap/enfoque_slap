import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Cliente } from '../interfaces/cliente';

@Injectable({
  providedIn: 'root'
})
export class ClientesService {

  constructor(private httpClient: HttpClient) { }

  public getCliente(cliente_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public getClientes(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes`;
    
    return this.httpClient.get(endpoint);
  }

  public updateCliente(cliente_id: number, cliente: Cliente): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${cliente_id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, cliente, httpOptions);
  }

  public storeCliente(cliente: Cliente): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, cliente, httpOptions);
  }

  public removeCliente(id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/clientes/${id}`;

    let httpOptions = { 
      method: 'DELETE',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };
    
    return this.httpClient.delete(endpoint, httpOptions);
  }

}
