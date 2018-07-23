class Booyah {

    constructor() {
        this.templateTagKeys = [];
        this.templateTagValues = [];
        this.templateArray = [];
        this.templateString = '';
    }

    ready() {
        this.templateString = this.templateArray.join('');
        this.replaceTags();
        return this.templateString;
    };

    addTag(key, value) {
        this.templateTagKeys.push(key);
        this.templateTagValues.push(value);
        return this;
    };

    addTemplate(string) {
        this.templateArray.push(string);
        return this;
    };

    replaceTags() {
        let i, replaceTag, tagCount, withThisValue;
        tagCount = this.templateTagKeys.length;
        i = 0;
        while (tagCount > i) {
            replaceTag = this.templateTagKeys[i];
            withThisValue = this.templateTagValues[i];
            this.templateString = this.templateString.replace(new RegExp(replaceTag), withThisValue);
            i++;
        }
    };

}

export { Booyah as default }