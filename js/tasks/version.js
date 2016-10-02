/**
 * Task: version
 * Set the versions in scripts.php for CSS/JS.
 */
'use strict';

module.exports = (gulp, $, Bricks) => {
    const crypto = require('crypto'),
          fs     = require('fs')
    
    const md5 = (filepath) => {
      let hash = crypto.createHash('md5')
      hash.update(fs.readFileSync(filepath))
      return hash.digest('hex')
    }

    let base = Bricks.path('presenter/Base.php')
    let hashCss = md5(Bricks.path('css/main.min.css')),
        hashJs  = md5(Bricks.path('css/main.min.js'))

    let regexCss = /static \$style_version\s*=\s*([^;]*)/,
        regexJs  = /static \$script_version\s*=\s*([^;]*)/

    fs.readFile(base, (err, data) => {
      data = data
        .replace(regexCss, "static $style_version = '"+ hashCss +"'")
        .replace(regexJs, "static $script_version = '"+ hashJs +"'")
      fs.writeFile(base, data, (err) => {
        if(err) throw err
        console.log('=D')
      })
    })

}
