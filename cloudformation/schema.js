const fs = require('fs');
const path = require('path');
const util = require('util');

exports.latest = async function () {
    const migrationFolder = path.join(__dirname, '../migrations/');
    const files = await util.promisify(fs.readdir)(migrationFolder);

    let latestVersion = null;
    for (const file of files) {
        const match = file.match(/^(.+)\.php/);
        if (match && (match[1] > latestVersion || latestVersion === null)) {
            latestVersion = match[1];
        }
    }

    if (latestVersion === null) {
        throw new Error("No migration found");
    }

    return `DoctrineMigrations\\${latestVersion}`;
};
