import {
   Controller
} from 'stimulus';

export default class extends Controller {
   static targets = ['legendList'];
   static values = {
   };

   load(event) {
      let colorArray = event.detail.colorArray;
      if (colorArray) {
         let content = '';
         for (var [key, value] of Object.entries(colorArray)) {
            content += '<div><span style="background-color:'+ value +'" title="'+ key+'">&nbsp;&nbsp;</span>&nbsp;<span>'+key+'</span></div>';
         }
         this.legendListTarget.innerHTML = content;
      }
   }
}