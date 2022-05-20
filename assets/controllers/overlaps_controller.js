import { Controller } from 'stimulus';

export default class extends Controller {
   static targets = ['card'];
   static values = {
      serviceUrl: String,
   };

   async load(event) {
      let params = new URLSearchParams({
         year: event.detail.year,
     });
     const response = await fetch(`${this.serviceUrlValue}?${params.toString()}`);
     this.cardTarget.innerHTML=await response.text();
   }
}