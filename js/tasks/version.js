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

    let base = Bricks.path('base/Base.php')
    let hashCss = md5(Bricks.path('assets/dist/bundle.css')),
        hashJs  = md5(Bricks.path('assets/dist/bundle.js'))

    let regexCss = /static \$styles_version\s*=\s*([^;]*)/,
        regexJs  = /static \$scripts_version\s*=\s*([^;]*)/

    fs.readFile(base, (err, data) => {
      if(err) throw err
      data = data
        .toString()
        .replace(regexCss, "static $styles_version = '"+ hashCss +"'")
        .replace(regexJs, "static $scripts_version = '"+ hashJs +"'")
      fs.writeFile(base, data, (err) => {
        if(err) throw err
      })
    })

}
