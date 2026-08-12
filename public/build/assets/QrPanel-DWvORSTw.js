import{_ as o}from"./Icon-vOvhQboq.js";import{c as l,d as r,t as m,i as p,a as n,g as d,k as u,o as b}from"./app-CzKaOZ6Z.js";const x={class:"rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 text-center"},h=["src","alt","width","height"],f={class:"mt-2 text-sm font-semibold text-slate-900 dark:text-white break-words"},y={key:0,class:"text-xs text-slate-500 dark:text-slate-400 break-words"},k={class:"mt-3 flex items-center justify-center gap-2"},w=["href"],v=["href"],$={__name:"QrPanel",props:{type:{type:String,required:!0},id:{type:[Number,String],required:!0},name:{type:String,required:!0},sub:{type:String,default:null},size:{type:Number,default:220}},setup(t){const e=t,i=u(()=>route("bookstore.labels.png",{type:e.type,id:e.id,size:e.size})),c=u(()=>route("bookstore.labels.sheet",{type:e.type,"ids[]":e.id}));function g(){const a=window.open("","_blank","width=420,height=560");a&&(a.document.write(`
        <html><head><title>${e.name}</title>
        <style>
            body { font-family: system-ui, sans-serif; text-align: center; margin: 24px; }
            img { width: 240px; height: 240px; }
            .name { font-size: 17px; font-weight: 700; margin-top: 10px; }
            .sub { font-size: 12px; color: #555; margin-top: 3px; }
            @media print { @page { margin: 8mm; } }
        </style></head>
        <body onload="window.print()">
            <img src="${i.value}" alt="QR">
            <div class="name">${e.name}</div>
            ${e.sub?`<div class="sub">${e.sub}</div>`:""}
        </body></html>
    `),a.document.close())}return(a,s)=>(b(),l("div",x,[r("img",{src:i.value,alt:`QR code for ${t.name}`,class:"mx-auto rounded-lg bg-white p-2",width:t.size,height:t.size},null,8,h),r("p",f,m(t.name),1),t.sub?(b(),l("p",y,m(t.sub),1)):p("",!0),r("div",k,[r("button",{type:"button",onClick:g,class:"inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition"},[n(o,{name:"Printer",size:14}),s[0]||(s[0]=d(" Print label ",-1))]),r("a",{href:i.value,download:"",class:"inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"},[n(o,{name:"Download",size:14}),s[1]||(s[1]=d(" Download ",-1))],8,w),r("a",{href:c.value,class:"inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"},[n(o,{name:"Copy",size:14}),s[2]||(s[2]=d(" Sheet ",-1))],8,v)])]))}};export{$ as _};
