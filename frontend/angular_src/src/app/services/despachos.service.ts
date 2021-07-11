import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class DespachosService {

  constructor(private httpClient: HttpClient) { }

  /*
   *  Sucursal (centro)
   */
  public removeDespacho_centrodistribucion(centrodistribucion_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/${despacho_id}`;

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

  public updateDespacho_centrodistribucion(centrodistribucion_id: number, despacho_id: number, despacho: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/${despacho_id}`;

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
    
    return this.httpClient.put(endpoint, despacho, httpOptions);
  }

  public prepareUpdateDespacho_centrodistribucion(centrodistribucion_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/${despacho_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getDespacho_centrodistribucion(centrodistribucion_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/${despacho_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeDespacho_centrodistribucion(centrodistribucion_id: number, despacho: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos`;

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

  public queueOcPartesDespacho_centrodistribucion(centrodistribucion_id: number, sucursal_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/queueocpartes/sucursales/${sucursal_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public prepareStoreDespacho_centrodistribucion(centrodistribucion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getDespachos_centrodistribucion(centrodistribucion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/despachos`;
    
    return this.httpClient.get(endpoint);
  }


  /*
   *  Comprador
   */
  public removeDespacho_comprador(comprador_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/${despacho_id}`;

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

  public updateDespacho_comprador(comprador_id: number, despacho_id: number, despacho: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/${despacho_id}`;

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
    
    return this.httpClient.put(endpoint, despacho, httpOptions);
  }

  public prepareUpdateDespacho_comprador(comprador_id: number, despacho_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/${despacho_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

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

  public queueOcPartesDespacho_comprador(comprador_id: number, centrodistribucion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/despachos/queueocpartes/centrosdistribucion/${centrodistribucion_id}`;
    
    return this.httpClient.get(endpoint);
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
