import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = ['card'];
   static values = {
      statsUrl: String,
   };

   async load(event) {
      let colorArray = event.detail.colorArray;
      let params = new URLSearchParams({
         year: event.detail.year,
         users: Object.keys(colorArray).toString(),
         colors: encodeURI(Object.values(colorArray).toString()),
     });
     const response = await fetch(`${this.statsUrlValue}?${params.toString()}`);
     this.cardTarget.innerHTML=await response.text();
   }
}
