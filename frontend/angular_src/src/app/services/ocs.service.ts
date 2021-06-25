import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class OcsService {

  constructor(
    private httpClient: HttpClient
  ) { }

  public startOC(oc_id: number, data: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/start/${oc_id}`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.post(endpoint, data, httpOptions);
  }

  public rejectOC(oc_id: number, data: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/reject/${oc_id}`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.post(endpoint, data, httpOptions);
  }

  public getMotivosBajaFull(): Observable<any> {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/motivosbaja/all`;
    
    return this.httpClient.get(endpoint);
  }

  public removeParte(oc_id: number, parte_id: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/${oc_id}/partes/${parte_id}`;

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

  public updateParte(oc_id: number, parte: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/${oc_id}/partes`;

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
    
    return this.httpClient.put(endpoint, parte, httpOptions);
  }

  public getReportOc(data: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/report`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'enctype': 'multipart/form-data',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.post(endpoint, data, httpOptions);
  }

  public getOCs(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/ocs`;
    
    return this.httpClient.get(endpoint);
  }

  // public removeOc(id: number): Observable<any>
  // {
  //   let endpoint: string = `${environment.ENDPOINT_BASE}/ocs/${id}`;

  //   let httpOptions = { 
  //     method: 'DELETE',
  //     headers:
  //     {
  //       'Accept': 'application/json',
  //       'Content-Type': 'application/json'
  //     }
  //   };
    
  //   return this.httpClient.delete(endpoint, httpOptions);
  // }
}
