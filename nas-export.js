let count = 1;
const maxBaulasten = 99;
const container = document.getElementById('baulasten');
const addBtn = document.getElementById('addBtn');
const removeBtn = document.getElementById('removeBtn');

function updateButtons() {
  removeBtn.disabled = (count <= 1);
  addBtn.disabled = (count >= maxBaulasten);
}

updateButtons();

addBtn.addEventListener('click', () => {
if (count >= maxBaulasten) return;
const feld = document.createElement('fieldset');
feld.className = 'baulast';
feld.id = `baulast_${++count}`;
feld.innerHTML = `<legend>Baulast ${count}</legend>
<div class="row"><div class="third">
<label for="typ_${count}"><i class="bi bi-houses"></i> Art/Name der Baulast</label>
<input type="text" id="typ_${count}" name="typ[]" list="baulast_typen_${count}" placeholder="auswählen oder eingeben …">
<datalist id="baulast_typen_${count}">
<option value="Abstandsfläche">
<option value="Anbauverpflichtung">
<option value="Brandschutzabstand">
<option value="Erschließung">
<option value="Gemeinsame Bauteile">
<option value="Kinderspielplatz">
<option value="Kfz-Stellplatz">
<option value="Nutzungsmaßbeschränkung">
<option value="Vereinigung">
</datalist></div><div class="third">
<label for="bez_${count}"><i class="bi bi-card-list"></i> Geschäftszeichen*</label>
<input type="text" id="bez_${count}" name="bez[]">
</div><div class="third">
<label for="date_${count}"><i class="bi bi-calendar-date"></i> Eintragung*</label>
<input id="date_${count}" type="date" name="date[]">
</div></div>
<label for="umring_${count}"><i class="bi bi-crosshair2"></i> Umringskoordinaten</label>
<textarea id="umring_${count}" name="umring[]" placeholder="Koordinatenliste" required></textarea>`;
container.appendChild(feld);
feld.scrollIntoView({behavior: 'smooth', block: 'start'});
updateButtons();
});
removeBtn.addEventListener('click', () => {
  if (count <= 1) return;
  const ok = confirm(`Baulast ${count} wirklich wieder entfernen ?`);
  if (!ok) return;
  const current = document.getElementById(`baulast_${count}`);
  current.style.height = current.offsetHeight + 'px';
  requestAnimationFrame(() => {
    current.classList.add('removing');
    current.style.height = '0px';
  });
  setTimeout(() => {
    current.remove();
    count--;
    updateButtons();
  }, 250);
});
