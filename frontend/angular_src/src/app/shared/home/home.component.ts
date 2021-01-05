import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  constructor() { }

  ngOnInit(): void {
    this.cleanScripts();
    this.loadScript('assets/dist/js/init.js');
  }

  private cleanScripts(): void
  {
    let scripts = document.getElementsByTagName('script');

    for(let i=0; i<scripts.length; i++)
    {
      scripts.item(i).remove();
    }
  }

  private loadScript(src: string)
  {
    var script = document.createElement("script");
    script.setAttribute("src", "assets/js/app.js");
    document.body.appendChild(script);
  }

}
