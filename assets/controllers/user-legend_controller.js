import {
   Controller
} from 'stimulus';

import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
   static targets = ['legendList','headerRow'];
   static values = {
      statsUrl: String,
   };

   connect() {
      Translator.fromJSON(translations);
      Translator.locale = this.localeValue;
   }

   async load(event) {
      let colorArray = event.detail.colorArray;
      let params = new URLSearchParams({
         year: event.detail.year,
         users: Object.keys(colorArray).toString(),
     });
     const response = await fetch(`${this.statsUrlValue}?${params.toString()}`)
         .then(result => result.json())
         .then(result => {
            console.log(result);
            if (colorArray) {
               let content = '';
               for (var [key, value] of Object.entries(colorArray)) {
                  content += '<tr>';
                  content += '<td><span style="background-color:'+ value +'" title="'+ key+'">&nbsp;&nbsp;</span>&nbsp;<span>'+key+'</span></td>'+this.createStatsRow(result,key);
                  content += '</tr>';
               }
               this.headerRowTarget.innerHTML=content;
            }
         });
   }

   createStatsRow(result,key) {
      let approved = result[key].approved ? result[key].approved : 0;
      let reserved = result[key].reserved ? result[key].reserved : 0;
      let total = result[key].total ? result[key].total - reserved - approved : result[key].total;
      let content = '';
      content += '<td style="background-color: green; text-align:center" title="'+Translator.trans('Approved')+'">&nbsp;'+approved+'&nbsp;</td>';
      content += '<td style="background-color: yellow; text-align:center" title="'+Translator.trans('Reserved')+'">&nbsp;'+reserved+'&nbsp;</td>';
      content += '<td style="background-color: aqua; text-align:center" title="'+Translator.trans('Remaining')+'">&nbsp;'+total+'&nbsp;</td>';
      return content;
   }
}
