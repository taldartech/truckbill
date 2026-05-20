import fs from 'fs-extra'

try {
  fs.copySync('./src/assets', './dist/assets')
} catch (error) {
  console.error(error)
}
