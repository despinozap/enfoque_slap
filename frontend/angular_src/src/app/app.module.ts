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
import { SolicitudesCreateComponent } from './pages/solicitudes/create/create.component';
import { SolicitudesListComponent } from './pages/solicitudes/list/list.component';
import { SolicitudesEditComponent } from './pages/solicitudes/edit/edit.component';


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
      { path: 'profile/edit', component: ProfileEditComponent },
      { path: 'profile/details', component: ProfileDetailsComponent },
      { path: 'usuarios/create', component: UsuariosCreateComponent },
      { path: 'usuarios', component: UsuariosListComponent },
      { path: 'usuarios/edit/:id', component: UsuariosEditComponent },
      { path: 'solicitudes/create', component: SolicitudesCreateComponent },
      { path: 'solicitudes', component: SolicitudesListComponent },
      { path: 'solicitudes/edit/:id', component: SolicitudesEditComponent },
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
    SolicitudesCreateComponent,
    SolicitudesListComponent,
    SolicitudesEditComponent
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
