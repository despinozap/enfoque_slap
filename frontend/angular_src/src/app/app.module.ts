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
import { SolicitudesDetailsAdministratorComponent } from './pages/solicitudes/details/administrator/administrator.component';
import { SolicitudesDetailsSellerComponent } from './pages/solicitudes/details/seller/seller.component';
import { PartesListComponent } from './pages/partes/list/list.component';
import { PartesEditComponent } from './pages/partes/edit/edit.component';
import { CotizacionesListComponent } from './pages/cotizaciones/list/list.component';
import { CotizacionesDetailsComponent } from './pages/cotizaciones/details/details.component';
import { PDFCotizacionComponent } from './pages/pdfs/cotizacion/cotizacion.component';
import { OcsListComponent } from './pages/ocs/list/list.component';


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
      { path: 'solicitudes/details/administrator/:id', component: SolicitudesDetailsAdministratorComponent },
      { path: 'solicitudes/details/seller/:id', component: SolicitudesDetailsSellerComponent },
      { path: 'partes', component: PartesListComponent },
      { path: 'partes/edit/:id', component: PartesEditComponent },
      { path: 'cotizaciones', component: CotizacionesListComponent },
      { path: 'cotizaciones/details/:id', component: CotizacionesDetailsComponent },
      { path: 'ocs', component: OcsListComponent },
    ]
  },
  { path: '**', redirectTo: 'login', pathMatch: 'full' } //any other page, redirects to base path
];

@NgModule({
  declarations: [
    AppComponent,
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
    SolicitudesDetailsAdministratorComponent,
    SolicitudesDetailsSellerComponent,
    PartesListComponent,
    PartesEditComponent,
    CotizacionesListComponent,
    CotizacionesDetailsComponent,
    PDFCotizacionComponent,
    OcsListComponent,
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
