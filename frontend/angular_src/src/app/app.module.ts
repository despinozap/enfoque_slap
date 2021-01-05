import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppComponent } from './app.component';

/* Routers */
import { RouterModule, Routes } from '@angular/router';

/* Shared */
import { HomeComponent } from './shared/home/home.component';
import { FooterComponent } from './shared/footer/footer.component';
import { MenubarComponent } from './shared/menubar/menubar.component';
import { TopbarComponent } from './shared/topbar/topbar.component';
import { TestComponent } from './pages/test/test.component';

/* Routes */
const routes: Routes = [
  { path: '', component: HomeComponent,
    children: [
      /* Pages */
      { path: '', component: TestComponent }
    ]
  },
  { path: '**', redirectTo: '', pathMatch: 'full' } //any other page, redirects to base path
];

@NgModule({
  declarations: [
    AppComponent,
    HomeComponent,
    FooterComponent,
    MenubarComponent,
    TopbarComponent
  ],
  imports: [
    BrowserModule,
    RouterModule.forRoot(routes)
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
