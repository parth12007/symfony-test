document.addEventListener('DOMContentLoaded', function() {
    const addSpecButton = document.createElement('button');
    addSpecButton.type = 'button';
    addSpecButton.className = 'btn btn-outline-primary mt-2';
    addSpecButton.textContent = 'Add Specification';

    const specificationsContainer = document.querySelector('#product_specifications');
    specificationsContainer.parentElement.appendChild(addSpecButton);

    let index = document.querySelectorAll('#product_specifications .row').length;

    addSpecButton.addEventListener('click', function() {
        const prototype = specificationsContainer.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, index);
        const div = document.createElement('div');
        div.innerHTML = newForm;
        div.className = 'row mb-2';
        
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-outline-danger btn-sm col-auto';
        removeButton.textContent = 'Remove';
        removeButton.onclick = function() {
            div.remove();
        };
        
        div.appendChild(removeButton);
        specificationsContainer.appendChild(div);
        index++;
    });
}); 