import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { AppComponent } from './app.component';

/* Routers */
import { RouterModule, Routes } from '@angular/router';

/* DataTables */
import { DataTablesModule } from 'angular-datatables';

/* Shared */
import { AuthService } from './services/auth.service';
import { LoadingComponent } from './components/loading/loading.component';
import { HomeComponent } from './shared/home/home.component';
import { FooterComponent } from './shared/footer/footer.component';
import { MenubarComponent } from './shared/menubar/menubar.component';
import { TopbarComponent } from './shared/topbar/topbar.component';
import { TestComponent } from './pages/test/test.component';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { AuthHttpInterceptor } from './interceptors/auth-http.interceptor';
import { LoginComponent } from './shared/login/login.component';
import { ResetComponent } from './shared/reset/reset.component';
import { AuthGuard } from './guards/auth.guard';
import { UsuariosCreateComponent } from './pages/usuarios/create/create.component';
import { UsuariosListComponent } from './pages/usuarios/list/list.component';
import { UsuariosEditComponent } from './pages/usuarios/edit/edit.component';
import { ProfileDetailsComponent } from './pages/profile/details/details.component';
import { ProfileEditComponent } from './pages/profile/edit/edit.component';
import { ParametersListComponent } from './pages/parameters/list/list.component';
import { ParametersEditComponent } from './pages/parameters/edit/edit.component';
import { ClientesListComponent } from './pages/clientes/list/list.component';
import { ClientesCreateComponent } from './pages/clientes/create/create.component';
import { ClientesEditComponent } from './pages/clientes/edit/edit.component';
import { FaenasListComponent } from './pages/clientes/faenas/list/list.component';
import { FaenasCreateComponent } from './pages/clientes/faenas/create/create.component';
import { FaenasEditComponent } from './pages/clientes/faenas/edit/edit.component';
import { SolicitudesCreateComponent } from './pages/solicitudes/create/create.component';
import { SolicitudesListComponent } from './pages/solicitudes/list/list.component';
import { SolicitudesEditComponent } from './pages/solicitudes/edit/edit.component';
import { SolicitudesCompleteComponent } from './pages/solicitudes/complete/complete.component';
import { SolicitudesDetailsComponent } from './pages/solicitudes/details/details.component';
import { PartesListComponent } from './pages/partes/list/list.component';
import { PartesEditComponent } from './pages/partes/edit/edit.component';
import { CotizacionesListComponent } from './pages/cotizaciones/list/list.component';
import { CotizacionesDetailsComponent } from './pages/cotizaciones/details/details.component';
import { CotizacionesEditComponent } from './pages/cotizaciones/edit/edit.component';
import { PDFCotizacionComponent } from './pages/pdfs/cotizacion/cotizacion.component';
import { CompradoresListComponent } from './pages/compradores/list/list.component';
import { CompradoresEditComponent } from './pages/compradores/edit/edit.component';
import { OcsListComponent } from './pages/ocs/list/list.component';
import { ProveedoresListComponent } from './pages/compradores/proveedores/list/list.component';
import { ProveedoresCreateComponent } from './pages/compradores/proveedores/create/create.component';
import { ProveedoresEditComponent } from './pages/compradores/proveedores/edit/edit.component';
import { OcsDetailsComponent } from './pages/ocs/details/details.component';
import { RecepcionesCompradorListComponent } from './pages/recepciones/comprador/list/list.component';
import { RecepcionesCompradorCreateComponent } from './pages/recepciones/comprador/create/create.component';
import { RecepcionesCompradorDetailsComponent } from './pages/recepciones/comprador/details/details.component';
import { RecepcionesCompradorEditComponent } from './pages/recepciones/comprador/edit/edit.component';
import { DespachosCompradorListComponent } from './pages/despachos/comprador/list/list.component';
import { DespachosCompradorCreateComponent } from './pages/despachos/comprador/create/create.component';
import { DespachosCompradorDetailsComponent } from './pages/despachos/comprador/details/details.component';
import { DespachosCompradorEditComponent } from './pages/despachos/comprador/edit/edit.component';
import { RecepcionesCentrodistribucionListComponent } from './pages/recepciones/centrodistribucion/list/list.component';
import { RecepcionesCentrodistribucionCreateComponent } from './pages/recepciones/centrodistribucion/create/create.component';
import { RecepcionesCentrodistribucionEditComponent } from './pages/recepciones/centrodistribucion/edit/edit.component';
import { RecepcionesCentrodistribucionDetailsComponent } from './pages/recepciones/centrodistribucion/details/details.component';
import { DespachosCentrodistribucionListComponent } from './pages/despachos/centrodistribucion/list/list.component';
import { DespachosCentrodistribucionCreateComponent } from './pages/despachos/centrodistribucion/create/create.component';
import { DespachosCentrodistribucionDetailsComponent } from './pages/despachos/centrodistribucion/details/details.component';
import { DespachosCentrodistribucionEditComponent } from './pages/despachos/centrodistribucion/edit/edit.component';
import { RecepcionesSucursalListComponent } from './pages/recepciones/sucursal/list/list.component';
import { RecepcionesSucursalEditComponent } from './pages/recepciones/sucursal/edit/edit.component';
import { RecepcionesSucursalCreateComponent } from './pages/recepciones/sucursal/create/create.component';
import { RecepcionesSucursalDetailsComponent } from './pages/recepciones/sucursal/details/details.component';
import { EntregasSucursalListComponent } from './pages/entregas/sucursal/list/list.component';
import { EntregasSucursalCreateComponent } from './pages/entregas/sucursal/create/create.component';
import { EntregasSucursalDetailsComponent } from './pages/entregas/sucursal/details/details.component';
import { EntregasSucursalEditComponent } from './pages/entregas/sucursal/edit/edit.component';
import { EntregasCentrodistribucionListComponent } from './pages/entregas/centrodistribucion/list/list.component';
import { EntregasCentrodistribucionCreateComponent } from './pages/entregas/centrodistribucion/create/create.component';
import { EntregasCentrodistribucionEditComponent } from './pages/entregas/centrodistribucion/edit/edit.component';
import { EntregasCentrodistribucionDetailsComponent } from './pages/entregas/centrodistribucion/details/details.component';
import { PDFOcComponent } from './pages/pdfs/oc/oc.component';


/* Routes */
const routes: Routes = [
  { path: 'login', component: LoginComponent }, //for the login page
  { path: 'reset', component: ResetComponent }, //for the login page
  {
    path: 'panel', component: HomeComponent,
    canActivate: [ AuthGuard ],
    children: [
      /* Pages */
      { path: '', component: TestComponent },
      { path: 'pdf', component: PDFCotizacionComponent },
      { path: 'profile/edit', component: ProfileEditComponent },
      { path: 'profile/details', component: ProfileDetailsComponent },
      { path: 'usuarios/create', component: UsuariosCreateComponent },
      { path: 'usuarios', component: UsuariosListComponent },
      { path: 'usuarios/edit/:id', component: UsuariosEditComponent },
      { path: 'parameters', component: ParametersListComponent },
      { path: 'parameters/edit/:id', component: ParametersEditComponent },
      { path: 'clientes/create', component: ClientesCreateComponent },
      { path: 'clientes', component: ClientesListComponent },
      { path: 'clientes/edit/:id', component: ClientesEditComponent },
      { path: 'clientes/:cliente_id/faenas/create', component: FaenasCreateComponent },
      { path: 'clientes/:cliente_id/faenas', component: FaenasListComponent },
      { path: 'clientes/:cliente_id/faenas/edit/:id', component: FaenasEditComponent },
      { path: 'solicitudes/create', component: SolicitudesCreateComponent },
      { path: 'solicitudes/create/:id', component: SolicitudesCreateComponent },
      { path: 'solicitudes', component: SolicitudesListComponent },
      { path: 'solicitudes/edit/:id', component: SolicitudesEditComponent },
      { path: 'solicitudes/complete/:id', component: SolicitudesCompleteComponent },
      { path: 'solicitudes/details/:id', component: SolicitudesDetailsComponent },
      { path: 'partes', component: PartesListComponent },
      { path: 'partes/edit/:id', component: PartesEditComponent },
      { path: 'cotizaciones', component: CotizacionesListComponent },
      { path: 'cotizaciones/details/:id', component: CotizacionesDetailsComponent },
      { path: 'cotizaciones/edit/:id', component: CotizacionesEditComponent },
      { path: 'compradores', component: CompradoresListComponent },
      { path: 'compradores/edit/:id', component: CompradoresEditComponent },
      { path: 'compradores/:comprador_id/proveedores/create', component: ProveedoresCreateComponent },
      { path: 'compradores/:comprador_id/proveedores', component: ProveedoresListComponent },
      { path: 'compradores/:comprador_id/proveedores/edit/:id', component: ProveedoresEditComponent },
      { path: 'ocs', component: OcsListComponent },
      { path: 'ocs/details/:id', component: OcsDetailsComponent },
      { path: 'recepciones/comprador', component: RecepcionesCompradorListComponent },
      { path: 'recepciones/comprador/create', component: RecepcionesCompradorCreateComponent },
      { path: 'recepciones/comprador/details/:id', component: RecepcionesCompradorDetailsComponent },
      { path: 'recepciones/comprador/edit/:id', component: RecepcionesCompradorEditComponent },
      { path: 'despachos/comprador', component: DespachosCompradorListComponent },
      { path: 'despachos/comprador/create', component: DespachosCompradorCreateComponent },
      { path: 'despachos/comprador/details/:id', component: DespachosCompradorDetailsComponent },
      { path: 'despachos/comprador/edit/:id', component: DespachosCompradorEditComponent },
      { path: 'recepciones/centrodistribucion', component: RecepcionesCentrodistribucionListComponent },
      { path: 'recepciones/centrodistribucion/create', component: RecepcionesCentrodistribucionCreateComponent },
      { path: 'recepciones/centrodistribucion/details/:id', component: RecepcionesCentrodistribucionDetailsComponent },
      { path: 'recepciones/centrodistribucion/edit/:id', component: RecepcionesCentrodistribucionEditComponent },
      { path: 'despachos/centrodistribucion', component: DespachosCentrodistribucionListComponent },
      { path: 'despachos/centrodistribucion/create', component: DespachosCentrodistribucionCreateComponent },
      { path: 'despachos/centrodistribucion/details/:id', component: DespachosCentrodistribucionDetailsComponent },
      { path: 'despachos/centrodistribucion/edit/:id', component: DespachosCentrodistribucionEditComponent },
      { path: 'entregas/centrodistribucion', component: EntregasCentrodistribucionListComponent },
      { path: 'entregas/centrodistribucion/create', component: EntregasCentrodistribucionCreateComponent },
      { path: 'entregas/centrodistribucion/details/:id', component: EntregasCentrodistribucionDetailsComponent },
      { path: 'entregas/centrodistribucion/edit/:id', component: EntregasCentrodistribucionEditComponent },
      { path: 'recepciones/sucursal', component: RecepcionesSucursalListComponent },
      { path: 'recepciones/sucursal/create', component: RecepcionesSucursalCreateComponent },
      { path: 'recepciones/sucursal/details/:id', component: RecepcionesSucursalDetailsComponent },
      { path: 'recepciones/sucursal/edit/:id', component: RecepcionesSucursalEditComponent },
      { path: 'entregas/sucursal', component: EntregasSucursalListComponent },
      { path: 'entregas/sucursal/create', component: EntregasSucursalCreateComponent },
      { path: 'entregas/sucursal/details/:id', component: EntregasSucursalDetailsComponent },
      { path: 'entregas/sucursal/edit/:id', component: EntregasSucursalEditComponent },
    ]
  },
  { path: '**', redirectTo: 'login', pathMatch: 'full' } //any other page, redirects to base path
];

@NgModule({
  declarations: [
    AppComponent,
    LoadingComponent,
    TestComponent,
    HomeComponent,
    FooterComponent,
    MenubarComponent,
    TopbarComponent,
    LoginComponent,
    ResetComponent,
    ProfileDetailsComponent,
    ProfileEditComponent,
    UsuariosCreateComponent,
    UsuariosListComponent,
    UsuariosEditComponent,
    ParametersListComponent,
    ParametersEditComponent,
    ClientesListComponent,
    ClientesCreateComponent,
    ClientesEditComponent,
    FaenasListComponent,
    FaenasCreateComponent,
    FaenasEditComponent,
    SolicitudesCreateComponent,
    SolicitudesListComponent,
    SolicitudesEditComponent,
    SolicitudesCompleteComponent,
    SolicitudesDetailsComponent,
    PartesListComponent,
    PartesEditComponent,
    CotizacionesListComponent,
    CotizacionesDetailsComponent,
    CotizacionesEditComponent,
    PDFCotizacionComponent,
    CompradoresListComponent,
    CompradoresEditComponent,
    ProveedoresListComponent,
    ProveedoresCreateComponent,
    ProveedoresEditComponent,
    OcsListComponent,
    OcsDetailsComponent,
    PDFOcComponent,
    RecepcionesCompradorListComponent,
    RecepcionesCompradorCreateComponent,
    RecepcionesCompradorDetailsComponent,
    RecepcionesCompradorEditComponent,
    DespachosCompradorListComponent,
    DespachosCompradorCreateComponent,
    DespachosCompradorDetailsComponent,
    DespachosCompradorEditComponent,
    RecepcionesCentrodistribucionListComponent,
    RecepcionesCentrodistribucionCreateComponent,
    RecepcionesCentrodistribucionEditComponent,
    RecepcionesCentrodistribucionDetailsComponent,
    DespachosCentrodistribucionListComponent,
    DespachosCentrodistribucionCreateComponent,
    DespachosCentrodistribucionDetailsComponent,
    DespachosCentrodistribucionEditComponent,
    RecepcionesSucursalListComponent,
    RecepcionesSucursalEditComponent,
    RecepcionesSucursalCreateComponent,
    RecepcionesSucursalDetailsComponent,
    EntregasSucursalListComponent,
    EntregasSucursalCreateComponent,
    EntregasSucursalDetailsComponent,
    EntregasSucursalEditComponent,
    EntregasCentrodistribucionListComponent,
    EntregasCentrodistribucionCreateComponent,
    EntregasCentrodistribucionEditComponent,
    EntregasCentrodistribucionDetailsComponent,    
  ],
  imports: [
    BrowserModule,
    RouterModule.forRoot(routes),
    HttpClientModule,
    ReactiveFormsModule,
    FormsModule,
    DataTablesModule
  ],
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthHttpInterceptor,
      multi: true
    },
    AuthService
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
