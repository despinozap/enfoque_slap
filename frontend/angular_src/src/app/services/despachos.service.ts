import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class DespachosService {

  constructor(private httpClient: HttpClient) { }

  // public updateRecepcion_comprador(comprador_id: number, recepcion_id: number, recepcion: any): Observable<any>
  // {
  //   let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}`;

  //   let httpOptions = { 
  //     method: 'PUT',
  //     headers:
  //     {
  //       'Accept': 'application/json',
  //       'Content-Type': 'application/json',
  //       'Access-Control-Allow-Origin': '*',
  //       'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
  //     }
  //   };
    
  //   return this.httpClient.put(endpoint, recepcion, httpOptions);
  // }

  // public prepareRecepcion_comprador(comprador_id: number, recepcion_id: number): Observable<any>
  // {
  //   let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}/prepare`;
    
  //   return this.httpClient.get(endpoint);
  // }

  public getDespacho_comprador(comprador_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/${despacho_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeDespacho_comprador(comprador_id: number, despacho: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, despacho, httpOptions);
  }

  public prepareStoreDespacho_comprador(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getDespachos_comprador(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos`;
    
    return this.httpClient.get(endpoint);
  }
}
