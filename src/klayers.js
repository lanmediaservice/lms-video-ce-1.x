/************************************************\
| KLayers 2.97                                   |
| DHTML Library for Internet Explorer 4.* - 6.*, |
| Netscape 4.* - 7.*, Mozilla, Opera 5.* - 7.*   |
| Copyright by Kruglov S. A. (kruglov.ru) 2003   |
\************************************************/

/***  See for description and latest version  ***\
\***  http://www.kruglov.ru/klayers/          ***/

function initKLayers(){
  isDOM=document.getElementById?true:false
  isOpera=isOpera5=window.opera && isDOM
  isOpera6=isOpera && window.print
  isOpera7=isOpera && document.readyState
  isMSIE=isIE=document.all && document.all.item && !isOpera
  isStrict=document.compatMode=='CSS1Compat'
  isNN=isNC=navigator.appName=="Netscape"
  isNN4=isNC4=isNN && !isDOM
  isMozilla=isNN6=isNN && isDOM

  if(!isDOM && !isNC && !isMSIE && !isOpera){
    KLayers=false
    return false
  }

  pageLeft=0
  pageTop=0

  KL_imgCount=0
  KL_imgArray=new Array()

  KL_imageRef="document.images[\""
  KL_imagePostfix="\"]"
  KL_styleSwitch=".style"
  KL_layerPostfix="\"]"

  if(isNN4){
    KL_layerRef="document.layers[\""
    KL_styleSwitch=""
  }

  if(isMSIE){
    KL_layerRef="document.all[\""
  }

  if(isDOM){
    KL_layerRef="document.getElementById(\""
    KL_layerPostfix="\")"
  }

  KLayers=true
  return true
}

initKLayers()

// document and window functions:

function KL_getBody(w){
  if(!w) w=window
  if(isStrict){
    return w.document.documentElement
  }else{
    return w.document.body
  }
}

function getWindowLeft(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return w.screenLeft
  if(isNN || isOpera) return w.screenX
}

function getWindowTop(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return w.screenTop
  if(isNN || isOpera) return w.screenY
}

function getWindowWidth(w){
  if(!w) w=window
  if(isMSIE) return KL_getBody(w).clientWidth
  if(isNN || isOpera) return w.innerWidth
}

function getWindowHeight(w){
  if(!w) w=window
  if(isMSIE) return KL_getBody(w).clientHeight
  if(isNN || isOpera) return w.innerHeight
}

function getDocumentWidth(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return KL_getBody(w).scrollWidth
  if(isNN) return w.document.width
  if(isOpera) return w.document.body.style.pixelWidth
}

function getDocumentHeight(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return KL_getBody(w).scrollHeight
  if(isNN) return w.document.height
  if(isOpera) return w.document.body.style.pixelHeight
}

function getScrollX(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return KL_getBody(w).scrollLeft
  if(isNN || isOpera) return w.pageXOffset
}

function getScrollY(w){
  if(!w) w=window
  if(isMSIE || isOpera7) return KL_getBody(w).scrollTop
  if(isNN || isOpera) return w.pageYOffset
}

function preloadImage(imageFile){
  KL_imgArray[KL_imgCount]=new Image()
  KL_imgArray[KL_imgCount++].src=imageFile
}

var KL_LAYER=0
var KL_IMAGE=1

function KL_findObject(what,where,type){
  var i,j,l,s
  var len=eval(where+".length")
  for(j=0;j<len;j++){
    s=where+"["+j+"].document.layers"
    if(type==KL_LAYER){
      l=s+"[\""+what+"\"]"
    }
    if(type==KL_IMAGE){
      i=where+"["+j+"].document.images"
      l=i+"[\""+what+"\"]"
    }
    if(eval(l)) return l
    l=KL_findObject(what,s,type)
    if(l!="null") return l
  }
  return "null"
}

function KL_getObjectPath(name,parent,type){
  var l=((parent && isNN4)?(parent+"."):(""))+((type==KL_LAYER)?KL_layerRef:KL_imageRef)+name+((type==KL_LAYER)?KL_layerPostfix:KL_imagePostfix)
  if(eval(l))return l
  if(!isNN4){
    return l
  }else{
    return KL_findObject(name,"document.layers",type)
  }
}

function layer(name){
  return new KLayer(name,null)
}

function layerFrom(name,parent){
  if(parent.indexOf("document.")<0) parent=layer(parent).path
  return new KLayer(name,parent)
}

function image(name){
  return new KImage(name,null)
}

function imageFrom(name,parent){
  if(parent.indexOf("document.")<0) parent=layer(parent).path
  return new KImage(name,parent)
}

// class "KLayer":

function KLayer(name,parent){
  this.path=KL_getObjectPath(name,parent,KL_LAYER)
  this.object=eval(this.path)
  if(!this.object)return
  this.style=this.css=eval(this.path+KL_styleSwitch)
}

KLP=KLayer.prototype

KLP.isExist=KLP.exists=function(){
  return (this.object)?true:false
}

function KL_getPageOffset(o){ 
  var KL_left=0
  var KL_top=0
  do{
    KL_left+=o.offsetLeft
    KL_top+=o.offsetTop
  }while(o=o.offsetParent)
  return [KL_left, KL_top]
}

KLP.getLeft=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera) return o.offsetLeft-pageLeft
  if(isNN4) return o.x-pageLeft
}

KLP.getTop=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera) return o.offsetTop-pageTop
  if(isNN4) return o.y-pageTop
}

KLP.getAbsoluteLeft=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera) return KL_getPageOffset(o)[0]-pageLeft
  if(isNN4) return o.pageX-pageLeft
}

KLP.getAbsoluteTop=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera) return KL_getPageOffset(o)[1]-pageTop
  if(isNN4) return o.pageY-pageTop
}

KLP.getWidth=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera7) return o.offsetWidth
  if(isOpera) return this.css.pixelWidth
  if(isNN4) return o.document.width
}

KLP.getHeight=function(){
  var o=this.object
  if(isMSIE || isMozilla || isOpera7) return o.offsetHeight
  if(isOpera) return this.css.pixelHeight
  if(isNN4) return o.document.height
}

KLP.getZIndex=function(){ //deprecated
  return this.css.zIndex
}

KLP.setLeft=KLP.moveX=function(x){
  x+=pageLeft
  if(isOpera){
    this.css.pixelLeft=x
  }else if(isNN4){
    this.object.x=x
  }else{
    this.css.left=x+"px"
  }
}

KLP.setTop=KLP.moveY=function(y){
  y+=pageTop
  if(isOpera){
    this.css.pixelTop=y
  }else if(isNN4){
    this.object.y=y
  }else{
    this.css.top=y+"px"
  }
}

KLP.moveTo=KLP.move=function(x,y){
  this.setLeft(x)
  this.setTop(y)
}

KLP.moveBy=function(x,y){
  this.moveTo(this.getLeft()+x,this.getTop()+y)
}

KLP.setZIndex=KLP.moveZ=function(z){ //deprecated
  this.css.zIndex=z
}

KLP.setVisibility=function(v){
  this.css.visibility=(v)?(isNN4?"show":"visible"):(isNN4?"hide":"hidden")
}

KLP.show=function(){
  this.setVisibility(true)
}

KLP.hide=function(){
  this.setVisibility(false)
}

KLP.isVisible=KLP.getVisibility=function(){
  return (this.css.visibility.toLowerCase().charAt(0)=='h')?false:true
}

KLP.setBgColor=function(c){
  if(isMSIE || isMozilla || isOpera7){
    this.css.backgroundColor=c
  }else if(isOpera){
    this.css.background=c
  }else if(isNN4){
    this.css.bgColor=c
  }
}

KLP.setBgImage=function(url){
  if(isMSIE || isMozilla || isOpera6){
    this.css.backgroundImage="url("+url+")"
  }else if(isNN4){
    this.css.background.src=url
  }
}

KLP.setClip=KLP.clip=function(top,right,bottom,left){
  if(isMSIE || isMozilla || isOpera7){
    this.css.clip="rect("+top+"px "+right+"px "+bottom+"px "+left+"px)"
  }else if(isNN4){
    var c=this.css.clip
    c.top=top
    c.right=right
    c.bottom=bottom
    c.left=left
  }
}

KLP.scrollTo=KLP.scroll=function(windowLeft,windowTop,windowWidth,windowHeight,scrollX,scrollY){
  if(scrollX>this.getWidth()-windowWidth) scrollX=this.getWidth()-windowWidth
  if(scrollY>this.getHeight()-windowHeight) scrollY=this.getHeight()-windowHeight
  if(scrollX<0)scrollX=0
  if(scrollY<0)scrollY=0
  var top=0
  var right=windowWidth
  var bottom=windowHeight
  var left=0
  left=left+scrollX
  right=right+scrollX
  top=top+scrollY
  bottom=bottom+scrollY
  this.moveTo(windowLeft-scrollX,windowTop-scrollY)
  this.setClip(top,right,bottom,left)
}

KLP.scrollBy=KLP.scrollByOffset=function(windowLeft,windowTop,windowWidth,windowHeight,scrollX,scrollY){
  var X=-parseInt(this.css.left)+windowLeft+scrollX
  var Y=-parseInt(this.css.top)+windowTop+scrollY
  this.scroll(windowLeft,windowTop,windowWidth,windowHeight,X,Y)
}

KLP.scrollByPercentage=function(windowLeft,windowTop,windowWidth,windowHeight,scrollX,scrollY){
  var X=(this.getWidth()-windowWidth)*scrollX/100
  var Y=(this.getHeight()-windowHeight)*scrollY/100
  this.scroll(windowLeft,windowTop,windowWidth,windowHeight,X,Y)
}

KLP.write=function(str){
  var o=this.object
  if(isNN4){
    var d=o.document
    d.open()
    d.write(str)
    d.close()
  }else{
    o.innerHTML=str
  }
}

KLP.add=function(str){
  var o=this.object
  if(isNN4){
    o.document.write(str)
  }else{
    o.innerHTML+=str
  }
}

// class "KImage":

KIP=KImage.prototype

function KImage(name){
  this.path=KL_getObjectPath(name,false,KL_IMAGE)
  this.object=eval(this.path)
}

KIP.isExist=KIP.exists=function(){
  return (this.object)?true:false
}

KIP.getSrc=KIP.src=function(){
  return this.object.src
}

KIP.setSrc=KIP.load=function(url){
  this.object.src=url
}