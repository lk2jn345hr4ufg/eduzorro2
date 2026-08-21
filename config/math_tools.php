<?php

/*
|--------------------------------------------------------------------------
| Math tools
|--------------------------------------------------------------------------
| Each entry drives a calculator rendered by resources/views/tools/partials/math.blade.php.
|
|   fields  — inputs. type: number (default) | text | select
|             label = a key inside the math.php language files
|   outputs — result rows, same label lookup
|   js      — body of a JS function receiving the field ids as arguments and
|             returning an object keyed by output id. Numbers are passed as
|             Number, text/select as String.
|
| Adding a tool = one entry here + one row in the admin (same slug).
*/

return [

    // ---------------------------------------------------------------- basics

    'percentage-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'value', 'default' => 250],
            ['id' => 'b', 'label' => 'percent', 'default' => 20],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'percent_of'],
            ['id' => 's', 'label' => 'what_percent'],
            ['id' => 'p', 'label' => 'plus_percent'],
            ['id' => 'm', 'label' => 'minus_percent'],
        ],
        'js' => 'return {r: a*b/100, s: b ? (a/b*100) : null, p: a + a*b/100, m: a - a*b/100};',
    ],

    'percentage-change-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'old_value', 'default' => 120],
            ['id' => 'b', 'label' => 'new_value', 'default' => 150],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'change_percent'],
            ['id' => 'd', 'label' => 'difference'],
        ],
        'js' => 'return {c: a ? ((b-a)/Math.abs(a)*100) : null, d: b-a};',
    ],

    'fraction-calculator' => [
        'fields'  => [
            ['id' => 'n1', 'label' => 'numerator', 'default' => 1],
            ['id' => 'd1', 'label' => 'denominator', 'default' => 2],
            ['id' => 'op', 'label' => 'operation', 'type' => 'select', 'default' => '+',
             'options' => ['+' => '+', '-' => '−', '*' => '×', '/' => '÷']],
            ['id' => 'n2', 'label' => 'numerator', 'default' => 1],
            ['id' => 'd2', 'label' => 'denominator', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'f', 'label' => 'result_fraction', 'text' => true],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'if(!d1||!d2) return {f:null,v:null};
                 var n,d;
                 if(op==="+"){n=n1*d2+n2*d1; d=d1*d2;}
                 else if(op==="-"){n=n1*d2-n2*d1; d=d1*d2;}
                 else if(op==="*"){n=n1*n2; d=d1*d2;}
                 else {n=n1*d2; d=d1*n2;}
                 if(!d) return {f:null,v:null};
                 function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x||1;}
                 var k=g(n,d); n/=k; d/=k;
                 if(d<0){n=-n;d=-d;}
                 return {f: n + "/" + d, v: n/d};',
    ],

    'ratio-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 16],
            ['id' => 'b', 'label' => 'b', 'default' => 9],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'simplified', 'text' => true],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x||1;}
                 if(!a||!b) return {r:null,v:null};
                 var k=g(a,b);
                 return {r: (a/k) + ":" + (b/k), v: a/b};',
    ],

    'proportion-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 3],
            ['id' => 'b', 'label' => 'b', 'default' => 4],
            ['id' => 'c', 'label' => 'c', 'default' => 9],
        ],
        'outputs' => [['id' => 'x', 'label' => 'x']],
        'js' => 'return {x: a ? (b*c/a) : null};',
    ],

    // ------------------------------------------------------------ statistics

    'average-calculator' => [
        'fields'  => [
            ['id' => 'list', 'label' => 'numbers_list', 'type' => 'text', 'default' => '4, 8, 15, 16, 23, 42'],
        ],
        'outputs' => [
            ['id' => 'mean', 'label' => 'mean'],
            ['id' => 'median', 'label' => 'median'],
            ['id' => 'sum', 'label' => 'sum'],
            ['id' => 'count', 'label' => 'count'],
            ['id' => 'min', 'label' => 'min'],
            ['id' => 'max', 'label' => 'max'],
        ],
        'js' => 'var v=String(list).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});
                 if(!v.length) return {};
                 var s=v.reduce(function(p,c){return p+c;},0);
                 var srt=v.slice().sort(function(p,c){return p-c;});
                 var mid=Math.floor(srt.length/2);
                 var med = srt.length%2 ? srt[mid] : (srt[mid-1]+srt[mid])/2;
                 return {mean:s/v.length, median:med, sum:s, count:v.length, min:srt[0], max:srt[srt.length-1]};',
    ],

    'standard-deviation-calculator' => [
        'fields'  => [
            ['id' => 'list', 'label' => 'numbers_list', 'type' => 'text', 'default' => '10, 12, 23, 23, 16, 23, 21, 16'],
        ],
        'outputs' => [
            ['id' => 'mean', 'label' => 'mean'],
            ['id' => 'vp', 'label' => 'variance_pop'],
            ['id' => 'sp', 'label' => 'sd_pop'],
            ['id' => 'ss', 'label' => 'sd_sample'],
        ],
        'js' => 'var v=String(list).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});
                 if(!v.length) return {};
                 var m=v.reduce(function(p,c){return p+c;},0)/v.length;
                 var sq=v.reduce(function(p,c){return p+(c-m)*(c-m);},0);
                 return {mean:m, vp:sq/v.length, sp:Math.sqrt(sq/v.length), ss: v.length>1 ? Math.sqrt(sq/(v.length-1)) : null};',
    ],

    // -------------------------------------------------------------- equations

    'quadratic-equation-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 1],
            ['id' => 'b', 'label' => 'b', 'default' => -3],
            ['id' => 'c', 'label' => 'c', 'default' => 2],
        ],
        'outputs' => [
            ['id' => 'd', 'label' => 'discriminant'],
            ['id' => 'x1', 'label' => 'x1', 'text' => true],
            ['id' => 'x2', 'label' => 'x2', 'text' => true],
        ],
        'js' => 'if(!a) return {d:null,x1:null,x2:null};
                 var D=b*b-4*a*c;
                 if(D>=0){var r1=(-b+Math.sqrt(D))/(2*a), r2=(-b-Math.sqrt(D))/(2*a);
                   return {d:D, x1:String(Math.round(r1*1e6)/1e6), x2:String(Math.round(r2*1e6)/1e6)};}
                 var re=Math.round((-b/(2*a))*1e6)/1e6, im=Math.round((Math.sqrt(-D)/(2*a))*1e6)/1e6;
                 return {d:D, x1:re+" + "+im+"i", x2:re+" − "+im+"i"};',
    ],

    'linear-equation-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 5],
            ['id' => 'b', 'label' => 'b', 'default' => -15],
        ],
        'outputs' => [['id' => 'x', 'label' => 'x']],
        'js' => 'return {x: a ? (-b/a) : null};',
    ],

    'system-of-equations-solver' => [
        'fields'  => [
            ['id' => 'a1', 'label' => 'a', 'default' => 2],
            ['id' => 'b1', 'label' => 'b', 'default' => 3],
            ['id' => 'c1', 'label' => 'c', 'default' => 12],
            ['id' => 'a2', 'label' => 'a2', 'default' => 4],
            ['id' => 'b2', 'label' => 'b2', 'default' => -1],
            ['id' => 'c2', 'label' => 'c2', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 'x', 'label' => 'x'],
            ['id' => 'y', 'label' => 'y'],
        ],
        'js' => 'var det=a1*b2-a2*b1;
                 if(!det) return {x:null,y:null};
                 return {x:(c1*b2-c2*b1)/det, y:(a1*c2-a2*c1)/det};',
    ],

    // ---------------------------------------------------------- number theory

    'gcd-lcm-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 48],
            ['id' => 'b', 'label' => 'b', 'default' => 18],
        ],
        'outputs' => [
            ['id' => 'g', 'label' => 'gcd'],
            ['id' => 'l', 'label' => 'lcm'],
        ],
        'js' => 'function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x;}
                 var G=g(a,b);
                 return {g:G, l: G ? Math.abs(a*b)/G : null};',
    ],

    'prime-number-checker' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 97],
        ],
        'outputs' => [
            ['id' => 'p', 'label' => 'is_prime', 'text' => true],
            ['id' => 'f', 'label' => 'factors', 'text' => true],
        ],
        'js' => 'n=Math.floor(Math.abs(n));
                 if(n<2) return {p:"—", f:"—"};
                 var m=n, fs=[];
                 for(var d=2; d*d<=m; d++){ while(m%d===0){ fs.push(d); m/=d; } }
                 if(m>1) fs.push(m);
                 return {p: fs.length===1 ? "✓" : "✗", f: fs.join(" × ")};',
    ],

    'factorial-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 10],
        ],
        'outputs' => [['id' => 'f', 'label' => 'factorial', 'text' => true]],
        'js' => 'n=Math.floor(n);
                 if(n<0||n>170) return {f:"—"};
                 var r=1; for(var i=2;i<=n;i++) r*=i;
                 return {f: r.toLocaleString("en-US", {maximumFractionDigits:0})};',
    ],

    'combinations-permutations-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'n', 'default' => 10],
            ['id' => 'r', 'label' => 'r', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'combinations'],
            ['id' => 'p', 'label' => 'permutations'],
        ],
        'js' => 'n=Math.floor(n); r=Math.floor(r);
                 if(n<0||r<0||r>n) return {c:null,p:null};
                 var P=1; for(var i=0;i<r;i++) P*=(n-i);
                 var R=1; for(var j=2;j<=r;j++) R*=j;
                 return {c:P/R, p:P};',
    ],

    // ------------------------------------------------------- powers and roots

    'exponent-calculator' => [
        'fields'  => [
            ['id' => 'b', 'label' => 'base', 'default' => 2],
            ['id' => 'e', 'label' => 'exponent', 'default' => 10],
        ],
        'outputs' => [['id' => 'r', 'label' => 'result']],
        'js' => 'return {r: Math.pow(b,e)};',
    ],

    'root-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 81],
            ['id' => 'n', 'label' => 'degree', 'default' => 2],
        ],
        'outputs' => [['id' => 'r', 'label' => 'root']],
        'js' => 'if(!n) return {r:null};
                 if(x<0 && n%2===0) return {r:null};
                 var s = x<0 ? -1 : 1;
                 return {r: s*Math.pow(Math.abs(x), 1/n)};',
    ],

    'logarithm-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 1000],
            ['id' => 'b', 'label' => 'base', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'log_base'],
            ['id' => 'n', 'label' => 'ln'],
            ['id' => 'l', 'label' => 'log10'],
        ],
        'js' => 'if(x<=0) return {};
                 return {r: (b>0 && b!==1) ? Math.log(x)/Math.log(b) : null, n: Math.log(x), l: Math.log(x)/Math.LN10};',
    ],

    // ------------------------------------------------------------- formatting

    'rounding-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 3.14159],
            ['id' => 'd', 'label' => 'decimals', 'default' => 2],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'rounded', 'text' => true],
            ['id' => 'f', 'label' => 'floor'],
            ['id' => 'c', 'label' => 'ceil'],
        ],
        'js' => 'd=Math.max(0,Math.min(15,Math.floor(d)));
                 return {r: x.toFixed(d), f: Math.floor(x), c: Math.ceil(x)};',
    ],

    'scientific-notation-converter' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 0.00042],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'scientific', 'text' => true],
            ['id' => 'e', 'label' => 'engineering', 'text' => true],
        ],
        'js' => 'if(x===0) return {s:"0", e:"0"};
                 var ex=Math.floor(Math.log10(Math.abs(x)));
                 var mant=x/Math.pow(10,ex);
                 var ee=Math.floor(ex/3)*3;
                 var em=x/Math.pow(10,ee);
                 return {s: (Math.round(mant*1e6)/1e6) + " × 10^" + ex,
                         e: (Math.round(em*1e6)/1e6) + " × 10^" + ee};',
    ],

    'number-base-converter' => [
        'fields'  => [
            ['id' => 'v', 'label' => 'number', 'type' => 'text', 'default' => '255'],
            ['id' => 'f', 'label' => 'from_base', 'type' => 'select', 'default' => '10',
             'options' => ['2' => '2', '8' => '8', '10' => '10', '16' => '16']],
        ],
        'outputs' => [
            ['id' => 'b', 'label' => 'binary', 'text' => true],
            ['id' => 'o', 'label' => 'octal', 'text' => true],
            ['id' => 'd', 'label' => 'decimal', 'text' => true],
            ['id' => 'h', 'label' => 'hex', 'text' => true],
        ],
        'js' => 'var n=parseInt(String(v).trim(), Number(f));
                 if(isNaN(n)) return {};
                 return {b:n.toString(2), o:n.toString(8), d:n.toString(10), h:n.toString(16).toUpperCase()};',
    ],

    'roman-numeral-converter' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 2024],
        ],
        'outputs' => [['id' => 'r', 'label' => 'roman', 'text' => true]],
        'js' => 'n=Math.floor(n);
                 if(n<1||n>3999) return {r:"—"};
                 var m=[[1000,"M"],[900,"CM"],[500,"D"],[400,"CD"],[100,"C"],[90,"XC"],
                        [50,"L"],[40,"XL"],[10,"X"],[9,"IX"],[5,"V"],[4,"IV"],[1,"I"]];
                 var s="";
                 for(var i=0;i<m.length;i++){ while(n>=m[i][0]){ s+=m[i][1]; n-=m[i][0]; } }
                 return {r:s};',
    ],

    // -------------------------------------------------------------- geometry

    'circle-calculator' => [
        'fields'  => [['id' => 'r', 'label' => 'radius', 'default' => 5]],
        'outputs' => [
            ['id' => 'a', 'label' => 'area'],
            ['id' => 'c', 'label' => 'circumference'],
            ['id' => 'd', 'label' => 'diameter'],
        ],
        'js' => 'return {a: Math.PI*r*r, c: 2*Math.PI*r, d: 2*r};',
    ],

    'triangle-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'side_a', 'default' => 3],
            ['id' => 'b', 'label' => 'side_b', 'default' => 4],
            ['id' => 'c', 'label' => 'side_c', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'h', 'label' => 'height_to_a'],
        ],
        'js' => 'if(a+b<=c||a+c<=b||b+c<=a) return {};
                 var sp=(a+b+c)/2;
                 var ar=Math.sqrt(sp*(sp-a)*(sp-b)*(sp-c));
                 return {s:ar, p:a+b+c, h: a ? (2*ar/a) : null};',
    ],

    'pythagorean-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'leg_a', 'default' => 3],
            ['id' => 'b', 'label' => 'leg_b', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'hypotenuse'],
            ['id' => 's', 'label' => 'area'],
        ],
        'js' => 'return {c: Math.sqrt(a*a+b*b), s: a*b/2};',
    ],

    'rectangle-calculator' => [
        'fields'  => [
            ['id' => 'w', 'label' => 'width', 'default' => 8],
            ['id' => 'h', 'label' => 'height', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 'a', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'd', 'label' => 'diagonal'],
        ],
        'js' => 'return {a:w*h, p:2*(w+h), d:Math.sqrt(w*w+h*h)};',
    ],

    'sphere-calculator' => [
        'fields'  => [['id' => 'r', 'label' => 'radius', 'default' => 5]],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: 4/3*Math.PI*r*r*r, s: 4*Math.PI*r*r};',
    ],

    'cylinder-calculator' => [
        'fields'  => [
            ['id' => 'r', 'label' => 'radius', 'default' => 3],
            ['id' => 'h', 'label' => 'height', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: Math.PI*r*r*h, s: 2*Math.PI*r*(r+h)};',
    ],

    'cone-calculator' => [
        'fields'  => [
            ['id' => 'r', 'label' => 'radius', 'default' => 3],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 'l', 'label' => 'slant'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'var l=Math.sqrt(r*r+h*h);
                 return {v: Math.PI*r*r*h/3, l:l, s: Math.PI*r*(r+l)};',
    ],

    'trapezoid-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'base_a', 'default' => 8],
            ['id' => 'b', 'label' => 'base_b', 'default' => 5],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'm', 'label' => 'midline'],
        ],
        'js' => 'return {s: (a+b)/2*h, m: (a+b)/2};',
    ],

    // ------------------------------------------------------ coordinate geometry

    'distance-between-points' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => 0],
            ['id' => 'y1', 'label' => 'y1', 'default' => 0],
            ['id' => 'x2', 'label' => 'x2', 'default' => 3],
            ['id' => 'y2', 'label' => 'y2', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'd', 'label' => 'distance'],
            ['id' => 'm', 'label' => 'midpoint', 'text' => true],
        ],
        'js' => 'return {d: Math.hypot(x2-x1, y2-y1),
                         m: "(" + ((x1+x2)/2) + "; " + ((y1+y2)/2) + ")"};',
    ],

    'slope-calculator' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => 1],
            ['id' => 'y1', 'label' => 'y1', 'default' => 2],
            ['id' => 'x2', 'label' => 'x2', 'default' => 4],
            ['id' => 'y2', 'label' => 'y2', 'default' => 8],
        ],
        'outputs' => [
            ['id' => 'k', 'label' => 'slope'],
            ['id' => 'b', 'label' => 'intercept'],
            ['id' => 'a', 'label' => 'angle_deg'],
        ],
        'js' => 'if(x1===x2) return {};
                 var k=(y2-y1)/(x2-x1);
                 return {k:k, b: y1-k*x1, a: Math.atan(k)*180/Math.PI};',
    ],

    'midpoint-calculator' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => -2],
            ['id' => 'y1', 'label' => 'y1', 'default' => 3],
            ['id' => 'x2', 'label' => 'x2', 'default' => 6],
            ['id' => 'y2', 'label' => 'y2', 'default' => 7],
        ],
        'outputs' => [
            ['id' => 'mx', 'label' => 'x'],
            ['id' => 'my', 'label' => 'y'],
        ],
        'js' => 'return {mx:(x1+x2)/2, my:(y1+y2)/2};',
    ],

    // ----------------------------------------------------------- applied math

    'simple-interest-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'principal', 'default' => 1000],
            ['id' => 'r', 'label' => 'rate_percent', 'default' => 5],
            ['id' => 't', 'label' => 'years', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'i', 'label' => 'interest'],
            ['id' => 'a', 'label' => 'total'],
        ],
        'js' => 'var i=p*r*t/100; return {i:i, a:p+i};',
    ],

    'compound-interest-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'principal', 'default' => 1000],
            ['id' => 'r', 'label' => 'rate_percent', 'default' => 5],
            ['id' => 't', 'label' => 'years', 'default' => 10],
            ['id' => 'n', 'label' => 'per_year', 'default' => 12],
        ],
        'outputs' => [
            ['id' => 'a', 'label' => 'total'],
            ['id' => 'i', 'label' => 'interest'],
        ],
        'js' => 'if(!n) return {};
                 var A=p*Math.pow(1+(r/100)/n, n*t);
                 return {a:A, i:A-p};',
    ],
];
